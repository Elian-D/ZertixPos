<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryWaste;
use App\Models\Products\Product;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Mermas (v1.5.0 REQ-2.2). Ver App\Models\Inventory\InventoryWaste.
 */
class InventoryWasteService
{
    public function __construct(protected InventoryMovementService $movements) {}

    /**
     * Registra y aplica la merma de inmediato. Todo o nada: si una línea no tiene stock
     * suficiente (o es un servicio), InventoryMovementService::register() lanza y no se
     * aplica ninguna.
     *
     * @param  array{warehouse_id: int, waste_date: string, notes?: ?string, reference_type?: ?string, reference_id?: ?int}  $data
     * @param  array<int, array{product_id: int, quantity: float, reason: string, notes?: ?string}>  $lines
     */
    public function create(array $data, array $lines): InventoryWaste
    {
        if (empty($lines)) {
            throw new DomainException('Agrega al menos una línea.');
        }

        return DB::transaction(function () use ($data, $lines) {
            $waste = InventoryWaste::create([
                'warehouse_id' => $data['warehouse_id'],
                'waste_date' => $data['waste_date'],
                'notes' => $data['notes'] ?? null,
                'status' => InventoryWaste::STATUS_APPLIED,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $costs = Product::whereIn('id', collect($lines)->pluck('product_id'))->pluck('cost', 'id');
            $total = 0;

            foreach ($lines as $line) {
                $qty = round((float) $line['quantity'], 2);
                $cost = (float) ($costs[$line['product_id']] ?? 0);
                $value = round($qty * $cost, 2);

                $waste->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $qty,
                    'reason' => $line['reason'],
                    'notes' => $line['notes'] ?? null,
                    'unit_cost' => $cost,
                    'total_value' => $value,
                ]);

                $this->movements->register([
                    'warehouse_id' => $waste->warehouse_id,
                    'product_id' => $line['product_id'],
                    'quantity' => -$qty,
                    'type' => InventoryMovement::TYPE_WASTE,
                    'description' => "Merma {$waste->number}",
                    'reference_type' => InventoryWaste::class,
                    'reference_id' => $waste->id,
                ]);

                $total += $value;
            }

            $waste->update(['total_value' => round($total, 2)]);

            return $waste;
        });
    }

    /**
     * Anular: devuelve el stock de cada línea con un 'waste' (+) que referencia la
     * merma. La merma queda Anulada con su motivo y usuario; no se edita ni se borra.
     *
     * Una merma que nació de otro documento (devolución dañada, REQ-2.3) no se anula sola:
     * devolvería al stock una unidad dañada. Se anula junto con su documento, que llama
     * aquí con $fromReference = true.
     */
    public function void(InventoryWaste $waste, string $reason, bool $fromReference = false): InventoryWaste
    {
        return DB::transaction(function () use ($waste, $reason, $fromReference) {
            $waste = InventoryWaste::whereKey($waste->id)->lockForUpdate()->firstOrFail();

            if ($waste->isVoided()) {
                throw new DomainException("La merma {$waste->number} ya está anulada.");
            }

            if ($waste->reference_type && ! $fromReference) {
                throw new DomainException($waste->origin['note'] ?? "La merma {$waste->number} viene de otro documento y no se anula sola.");
            }

            foreach ($waste->items as $item) {
                $this->movements->register([
                    'warehouse_id' => $waste->warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'type' => InventoryMovement::TYPE_WASTE,
                    'description' => "Anulación de merma {$waste->number}",
                    'reference_type' => InventoryWaste::class,
                    'reference_id' => $waste->id,
                ]);
            }

            $waste->update([
                'status' => InventoryWaste::STATUS_VOIDED,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $waste;
        });
    }
}
