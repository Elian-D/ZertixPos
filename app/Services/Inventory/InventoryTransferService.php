<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryTransfer;
use App\Models\Inventory\InventoryWasteItem;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Transferencias entre almacenes (v1.5.0 REQ-2.4). Ver App\Models\Inventory\InventoryTransfer.
 */
class InventoryTransferService
{
    public function __construct(
        protected InventoryMovementService $movements,
        protected InventoryWasteService $wastes,
    ) {}

    /**
     * @param  array{from_warehouse_id: int, to_warehouse_id: int, notes?: ?string}  $data
     * @param  array<int, array{product_id: int, quantity: float, notes?: ?string}>  $lines
     */
    public function create(array $data, array $lines): InventoryTransfer
    {
        return DB::transaction(function () use ($data, $lines) {
            $transfer = InventoryTransfer::create([
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'notes' => $data['notes'] ?? null,
                'status' => InventoryTransfer::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $this->syncLines($transfer, $lines);

            return $transfer;
        });
    }

    /** Solo en borrador: se reemplazan almacenes, nota y líneas. */
    public function update(InventoryTransfer $transfer, array $data, array $lines): InventoryTransfer
    {
        $this->assertStatus($transfer, InventoryTransfer::STATUS_DRAFT, 'editar');

        return DB::transaction(function () use ($transfer, $data, $lines) {
            $transfer->update([
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            $transfer->items()->delete();
            $this->syncLines($transfer, $lines);

            return $transfer;
        });
    }

    /**
     * Enviar: cada línea sale del origen con 'transfer_out' (−) y queda en tránsito.
     * Todo o nada: si una línea no tiene existencia suficiente en el origen, no sale ninguna.
     */
    public function send(InventoryTransfer $transfer): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = $this->lock($transfer);
            $this->assertStatus($transfer, InventoryTransfer::STATUS_DRAFT, 'enviar');

            foreach ($transfer->items as $item) {
                $this->movements->register([
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'to_warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity_sent,
                    'type' => InventoryMovement::TYPE_TRANSFER_OUT,
                    'description' => "Transferencia {$transfer->number} hacia {$transfer->toWarehouse->name}",
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                ]);
            }

            $transfer->update([
                'status' => InventoryTransfer::STATUS_SENT,
                'sent_by' => Auth::id(),
                'sent_at' => now(),
            ]);

            return $transfer;
        });
    }

    /**
     * Recibir: $received = [item_id => cantidad recibida] (por defecto, la enviada; nunca
     * más). Entra lo enviado al destino con 'transfer_in' (+) y, si llegó menos, la
     * diferencia sale enseguida como merma "Pérdida en tránsito" enlazada a la
     * transferencia. El destino queda con lo que de verdad llegó, y el kardex muestra
     * cuánto se perdió en el camino.
     */
    public function receive(InventoryTransfer $transfer, array $received): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $received) {
            $transfer = $this->lock($transfer);
            $this->assertStatus($transfer, InventoryTransfer::STATUS_SENT, 'recibir');

            $losses = [];

            foreach ($transfer->items as $item) {
                $sent = (float) $item->quantity_sent;
                $got = array_key_exists($item->id, $received) && $received[$item->id] !== null && $received[$item->id] !== ''
                    ? round((float) $received[$item->id], 2)
                    : $sent;

                if ($got < 0 || $got > $sent) {
                    throw new DomainException("La cantidad recibida de {$item->product->name} debe estar entre 0 y {$sent}.");
                }

                $item->update(['quantity_received' => $got]);

                $this->movements->register([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'to_warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $sent,
                    'type' => InventoryMovement::TYPE_TRANSFER_IN,
                    'description' => "Transferencia {$transfer->number} desde {$transfer->fromWarehouse->name}",
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                ]);

                if ($got < $sent) {
                    $losses[] = [
                        'product_id' => $item->product_id,
                        'quantity' => round($sent - $got, 2),
                        'reason' => InventoryWasteItem::REASON_TRANSIT_LOSS,
                    ];
                }
            }

            if ($losses) {
                $this->wastes->create([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'waste_date' => now()->toDateString(),
                    'notes' => "Pérdida en tránsito de la transferencia {$transfer->number}",
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                ], $losses);
            }

            $transfer->update([
                'status' => InventoryTransfer::STATUS_RECEIVED,
                'received_by' => Auth::id(),
                'received_at' => now(),
            ]);

            return $transfer;
        });
    }

    /** Solo desde borrador: no movió stock. No se borra porque el número ya se emitió. */
    public function cancel(InventoryTransfer $transfer): InventoryTransfer
    {
        $this->assertStatus($transfer, InventoryTransfer::STATUS_DRAFT, 'cancelar');

        $transfer->update([
            'status' => InventoryTransfer::STATUS_CANCELED,
            'canceled_by' => Auth::id(),
            'canceled_at' => now(),
        ]);

        return $transfer;
    }

    private function syncLines(InventoryTransfer $transfer, array $lines): void
    {
        if (empty($lines)) {
            throw new DomainException('Agrega al menos una línea.');
        }

        foreach ($lines as $line) {
            $transfer->items()->create([
                'product_id' => $line['product_id'],
                'quantity_sent' => round((float) $line['quantity'], 2),
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    private function lock(InventoryTransfer $transfer): InventoryTransfer
    {
        return InventoryTransfer::whereKey($transfer->id)->lockForUpdate()
            ->with(['items.product:id,name', 'fromWarehouse:id,name', 'toWarehouse:id,name'])
            ->firstOrFail();
    }

    private function assertStatus(InventoryTransfer $transfer, string $status, string $action): void
    {
        if ($transfer->status !== $status) {
            throw new DomainException("No se puede {$action} la transferencia {$transfer->number}: está {$transfer->status_label}.");
        }
    }
}
