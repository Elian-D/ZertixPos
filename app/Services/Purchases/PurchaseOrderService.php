<?php

namespace App\Services\Purchases;

use App\Models\Products\Product;
use App\Models\Purchases\PurchaseOrder;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Órdenes de compra (v1.5.0 REQ-5.1). Ver App\Models\Purchases\PurchaseOrder. Recibir vive
 * en PurchaseReceptionService.
 */
class PurchaseOrderService
{
    /**
     * @param  array{vendor_id: int, warehouse_id: int, order_date: string, expected_date?: ?string, payment_condition: string, financial_account_id?: ?int, notes?: ?string}  $data
     * @param  array<int, array{product_id: int, quantity: float, unit_cost: float}>  $lines
     */
    public function create(array $data, array $lines): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $lines) {
            $order = PurchaseOrder::create($this->header($data) + [
                'status' => PurchaseOrder::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $this->syncLines($order, $lines);

            return $order;
        });
    }

    /** Solo en borrador: se reemplazan encabezado y líneas. */
    public function update(PurchaseOrder $order, array $data, array $lines): PurchaseOrder
    {
        $this->assertStatus($order, [PurchaseOrder::STATUS_DRAFT], 'editar');

        return DB::transaction(function () use ($order, $data, $lines) {
            $order->update($this->header($data));

            $order->items()->delete();
            $this->syncLines($order, $lines);

            return $order;
        });
    }

    /** Borrador → Aprobada: ya no se edita y se puede recibir. No mueve stock ni dinero. */
    public function approve(PurchaseOrder $order): PurchaseOrder
    {
        $this->assertStatus($order, [PurchaseOrder::STATUS_DRAFT], 'aprobar');

        $order->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $order;
    }

    /** "Enviada al proveedor": un atributo, no un estado; no bloquea recibir. */
    public function markSent(PurchaseOrder $order): PurchaseOrder
    {
        $this->assertStatus($order, PurchaseOrder::RECEIVABLE_STATUSES, 'marcar como enviada');

        if ($order->sent_at) {
            throw new DomainException("La orden {$order->number} ya está marcada como enviada.");
        }

        $order->update(['sent_at' => now(), 'sent_by' => Auth::id()]);

        return $order;
    }

    /**
     * Desde borrador o aprobada, sin recepciones. No se borra: el número ya se emitió. Una
     * orden que ya recibió mercancía no se cancela; lo que falta se cierra con una
     * recepción que lo rechaza.
     */
    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->receptions()->exists()) {
                throw new DomainException("La orden {$order->number} ya tiene recepciones: no se puede cancelar. Lo que falta se cierra con una recepción que lo rechace.");
            }

            $this->assertStatus($order, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_APPROVED], 'cancelar');

            $order->update([
                'status' => PurchaseOrder::STATUS_CANCELED,
                'canceled_by' => Auth::id(),
                'canceled_at' => now(),
            ]);

            return $order;
        });
    }

    private function header(array $data): array
    {
        $cash = $data['payment_condition'] === PurchaseOrder::CONDITION_CASH;

        return [
            'vendor_id' => $data['vendor_id'],
            'warehouse_id' => $data['warehouse_id'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'payment_condition' => $data['payment_condition'],
            // La cuenta sugerida solo tiene sentido de contado.
            'financial_account_id' => $cash ? ($data['financial_account_id'] ?? null) : null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function syncLines(PurchaseOrder $order, array $lines): void
    {
        if (empty($lines)) {
            throw new DomainException('Agrega al menos una línea.');
        }

        // Solo productos tipo Producto (un servicio no tiene stock) y sin repetir: una línea
        // por producto, para que lo pendiente de cada uno sea claro al recibir.
        $productIds = array_map(fn ($l) => (int) $l['product_id'], $lines);
        if (count($productIds) !== count(array_unique($productIds))) {
            throw new DomainException('Hay un producto repetido: usa una sola línea por producto.');
        }

        $services = Product::whereIn('id', $productIds)->where('type', '!=', Product::TYPE_PRODUCT)->pluck('name');
        if ($services->isNotEmpty()) {
            throw new DomainException('"'.$services->first().'" es un servicio: no se compra para inventario.');
        }

        $total = 0;
        foreach ($lines as $line) {
            $qty = round((float) $line['quantity'], 2);
            $cost = round((float) $line['unit_cost'], 4);
            $subtotal = round($qty * $cost, 2);
            $total += $subtotal;

            $order->items()->create([
                'product_id' => $line['product_id'],
                'quantity' => $qty,
                'unit_cost' => $cost,
                'subtotal' => $subtotal,
            ]);
        }

        $order->update(['total' => round($total, 2)]);
    }

    private function assertStatus(PurchaseOrder $order, array $statuses, string $action): void
    {
        if (! in_array($order->status, $statuses, true)) {
            throw new DomainException("No se puede {$action} la orden {$order->number}: está ".mb_strtolower($order->status_label).'.');
        }
    }
}
