<?php

namespace App\Services\Purchases;

use App\Models\Accounting\FinancialAccount;
use App\Models\Inventory\InventoryMovement;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseReception;
use App\Models\Purchases\PurchaseReceptionItem;
use App\Models\Purchases\Vendor;
use App\Services\Accounting\Payables\PayableService;
use App\Services\Inventory\InventoryMovementService;
use App\Services\Products\ProductCostService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Recepciones de compra (v1.5.0 REQ-5.2 a REQ-5.4). Ver App\Models\Purchases\PurchaseReception.
 */
class PurchaseReceptionService
{
    public function __construct(
        protected InventoryMovementService $movements,
        protected ProductCostService $costs,
        protected PayableService $payables,
    ) {}

    /**
     * Recibe una entrega de la orden, todo en una transacción (REQ-5.3). Por cada línea con
     * algo recibido:
     *   1. El costo promedio se recalcula ANTES de sumar el stock (la fórmula usa el previo).
     *   2. Entra el stock con un movimiento 'purchase' (+) en el almacén de la orden.
     * Después, sobre el total recibido (lo rechazado no se paga):
     *   - Contado: nace la CxP y se paga completa desde la cuenta, aunque la deje en negativo
     *     (la mercancía ya entró; la cuenta se concilia después).
     *   - Crédito: nace la CxP pendiente, con vencimiento = fecha + días de crédito del proveedor.
     * La orden queda Recibida si no queda pendiente en ninguna línea; si no, Recibida parcial.
     *
     * Sin asiento contable (Decisión 4 de v1.5.0): iría aquí, después del pago.
     *
     * @param  array{reception_date: string, supplier_invoice?: ?string, supplier_ncf?: ?string, financial_account_id?: ?int, notes?: ?string, rating?: ?string, rating_note?: ?string}  $data
     * @param  array<int, array{received?: mixed, rejected?: mixed, reason?: ?string, unit_cost?: mixed}>  $lines  por id de la línea de la orden
     */
    public function receive(PurchaseOrder $order, array $data, array $lines): PurchaseReception
    {
        return DB::transaction(function () use ($order, $data, $lines) {
            $order = PurchaseOrder::whereKey($order->id)->lockForUpdate()
                ->with(['items.product', 'vendor', 'warehouse:id,name'])->firstOrFail();

            if (! $order->canReceive()) {
                throw new DomainException("No se puede recibir la orden {$order->number}: está ".mb_strtolower($order->status_label).'.');
            }

            $rows = $this->validateLines($order, $lines);

            if (! $rows) {
                throw new DomainException('Indica en al menos una línea lo que se recibe o se rechaza.');
            }

            $total = round(array_sum(array_column($rows, 'subtotal')), 2);
            $account = null;

            if ($order->isCash() && $total > 0) {
                $account = FinancialAccount::find($data['financial_account_id'] ?? null);
                if (! $account) {
                    throw new DomainException('Elige la cuenta de la que sale el pago.');
                }
            }

            $reception = PurchaseReception::create([
                'purchase_order_id' => $order->id,
                'reception_date' => $data['reception_date'],
                'supplier_invoice' => $data['supplier_invoice'] ?? null,
                'supplier_ncf' => $data['supplier_ncf'] ?? null,
                'financial_account_id' => $account?->id,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                // Omitir la calificación (REQ-5.4) la deja en null, nunca en ''.
                'rating' => ($data['rating'] ?? null) ?: null,
                'rating_note' => ! empty($data['rating']) ? (($data['rating_note'] ?? null) ?: null) : null,
                'created_by' => Auth::id(),
            ]);

            foreach ($rows as $row) {
                $item = $row['item'];

                $reception->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity_received' => $row['received'],
                    'quantity_rejected' => $row['rejected'],
                    'reject_reason' => $row['rejected'] > 0 ? $row['reason'] : null,
                    'unit_cost' => $row['unit_cost'],
                    'subtotal' => $row['subtotal'],
                ]);

                if ($row['received'] > 0) {
                    $this->costs->applyIncoming($item->product, $row['received'], $row['unit_cost']);

                    $this->movements->register([
                        'warehouse_id' => $order->warehouse_id,
                        'product_id' => $item->product_id,
                        'quantity' => $row['received'],
                        'unit_cost' => $row['unit_cost'],
                        'type' => InventoryMovement::TYPE_PURCHASE,
                        'description' => "Recepción {$reception->number} de la orden {$order->number} · {$order->vendor->display_name}",
                        'reference_type' => PurchaseReception::class,
                        'reference_id' => $reception->id,
                    ]);
                }

                $item->update([
                    'quantity_received' => round((float) $item->quantity_received + $row['received'], 2),
                    'quantity_rejected' => round((float) $item->quantity_rejected + $row['rejected'], 2),
                ]);
            }

            if ($total > 0) {
                $this->createPayable($order, $reception, $account);
            }

            $order->update([
                'status' => $order->items->every(fn ($i) => $i->pending <= 0)
                    ? PurchaseOrder::STATUS_RECEIVED
                    : PurchaseOrder::STATUS_PARTIAL,
            ]);

            if ($reception->rating) {
                $this->refreshVendorRating($order->vendor);
            }

            return $reception;
        });
    }

    /**
     * Caché `vendors.rating` (REQ-5.4): promedio de todas sus recepciones calificadas
     * (A = 4 … D = 1), redondeado al grado más cercano. Sin calificaciones, null.
     */
    public function refreshVendorRating(Vendor $vendor): void
    {
        $ratings = PurchaseReception::whereNotNull('rating')
            ->whereHas('order', fn ($q) => $q->where('vendor_id', $vendor->id))
            ->pluck('rating');

        $rating = null;
        if ($ratings->isNotEmpty()) {
            $avg = $ratings->avg(fn ($r) => PurchaseReception::RATINGS[$r] ?? 0);
            $rating = array_search((int) round($avg), PurchaseReception::RATINGS, true) ?: null;
        }

        // rating no es fillable a propósito: nunca llega desde el formulario del proveedor.
        $vendor->forceFill(['rating' => $rating])->save();
    }

    /**
     * Valida y normaliza las líneas. Una línea sin nada recibido ni rechazado se omite.
     * Recibido + rechazado no puede superar lo pendiente; rechazar exige motivo.
     *
     * @return array<int, array{item: \App\Models\Purchases\PurchaseOrderItem, received: float, rejected: float, reason: ?string, unit_cost: float, subtotal: float}>
     */
    private function validateLines(PurchaseOrder $order, array $lines): array
    {
        $reasons = PurchaseReceptionItem::getRejectReasons();
        $rows = [];

        foreach ($order->items as $item) {
            $line = $lines[$item->id] ?? [];
            $received = round((float) ($line['received'] ?? 0), 2);
            $rejected = round((float) ($line['rejected'] ?? 0), 2);
            $name = $item->product->name ?? 'un producto';

            if ($received < 0 || $rejected < 0) {
                throw new DomainException("Las cantidades de {$name} no pueden ser negativas.");
            }

            if ($received == 0 && $rejected == 0) {
                continue;
            }

            if ($received + $rejected > $item->pending) {
                throw new DomainException("En {$name}, lo recibido más lo rechazado no puede superar lo pendiente ({$this->qty($item->pending)}).");
            }

            $reason = $line['reason'] ?? null;
            if ($rejected > 0 && ! array_key_exists((string) $reason, $reasons)) {
                throw new DomainException("Indica el motivo del rechazo de {$name}.");
            }

            $cost = array_key_exists('unit_cost', $line) && $line['unit_cost'] !== null && $line['unit_cost'] !== ''
                ? round((float) $line['unit_cost'], 4)
                : (float) $item->unit_cost;

            if ($cost < 0) {
                throw new DomainException("El costo de {$name} no puede ser negativo.");
            }

            $rows[] = [
                'item' => $item,
                'received' => $received,
                'rejected' => $rejected,
                'reason' => $reason,
                'unit_cost' => $cost,
                'subtotal' => round($received * $cost, 2),
            ];
        }

        return $rows;
    }

    private function createPayable(PurchaseOrder $order, PurchaseReception $reception, ?FinancialAccount $account): void
    {
        $date = $reception->reception_date->toDateString();
        $vendor = $order->vendor;

        $payable = $this->payables->createFor($reception, [
            'vendor_id' => $vendor->id,
            'concept' => "Compra · orden {$order->number}, recepción {$reception->number}",
            'total_amount' => (float) $reception->total,
            'supplier_invoice' => $reception->supplier_invoice ?: $reception->supplier_ncf,
            'issue_date' => $date,
            'due_date' => $order->isCash()
                ? $date
                : Carbon::parse($date)->addDays((int) $vendor->credit_days)->toDateString(),
        ]);

        if ($order->isCash()) {
            // fromDocument: la mercancía ya entró; no se frena por el saldo de la cuenta.
            $this->payables->pay($payable, $account, (float) $reception->total, $date,
                $reception->supplier_invoice, "Pago de contado de la recepción {$reception->number}", fromDocument: true);
        }
    }

    private function qty(float $q): string
    {
        return rtrim(rtrim(number_format($q, 2), '0'), '.');
    }
}
