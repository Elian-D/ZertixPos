<?php

namespace App\Services\Sales\Returns;

use App\Models\Accounting\DocumentType;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\InventoryMovement;
use App\Models\Products\Product;
use App\Models\Sales\Returns\SaleReturn;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleItem;
use App\Services\Accounting\Receivable\ReceivableService;
use App\Services\Inventory\InventoryMovementService;
use App\Services\Sales\SalesServices\SaleService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Devoluciones y Cambios desde backoffice (v1.4.0 Fase 2, ver docs/features/v1.4.0.md §2.3).
 * Se ejecutan en el acto, sin aprobación. El efectivo solo se registra — cero caja,
 * turno o contabilidad. Los permisos se validan en quien llama (componente Livewire).
 */
class ReturnService
{
    public function __construct(
        protected InventoryMovementService $inventoryService,
        protected ReceivableService $receivableService,
        protected SaleService $saleService
    ) {}

    /**
     * @param  array{
     *     lines: array<int, array{sale_item_id: int, quantity: float, restock?: bool}>,
     *     reason: string,
     *     refund_method?: string,
     *     replacement_product_id?: int|null,
     *     replacement_quantity?: float|null,
     *     notes?: string|null,
     * }  $data  refund_method: 'cash' | 'exchange'. En venta a crédito, 'cash' se
     *           convierte en reducción de deuda ('receivable'). En efectivo se
     *           entrega exactamente el valor devuelto.
     */
    public function create(Sale $sale, array $data): SaleReturn
    {
        return DB::transaction(function () use ($sale, $data) {
            // Bloqueo de la venta: dos devoluciones simultáneas de la misma línea no
            // deben poder pasar ambas la validación de cantidad disponible.
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);

            if ($sale->status !== Sale::STATUS_COMPLETED) {
                throw new Exception('Solo se pueden devolver ventas completadas.');
            }

            if ($sale->canBeCanceled()) {
                throw new Exception('Esta venta pertenece a un turno de caja abierto — se anula, no se devuelve.');
            }

            if (! array_key_exists($data['reason'] ?? '', SaleReturn::getReasons())) {
                throw new Exception('Motivo de devolución no válido.');
            }

            $lines = $this->buildLines($sale, $data['lines'] ?? []);
            $refundValue = round(array_sum(array_column($lines, 'total')), 2);
            $method = $this->resolveMethod($sale, $data['refund_method'] ?? null);

            if ($method === SaleReturn::METHOD_EXCHANGE && count($lines) > 1) {
                throw new Exception('El cambio de producto es de uno en uno. Con varios productos, la devolución es en efectivo o reduce la deuda.');
            }

            $docType = DocumentType::where('code', 'DEV')->firstOrFail();
            $number = $docType->getNextNumberFormatted();
            $docType->increment('current_number');

            $return = SaleReturn::create([
                'document_type_id' => $docType->id,
                'number' => $number,
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'reason' => $data['reason'],
                'refund_method' => $method,
                'refund_value' => $refundValue,
                'cash_amount' => $method === SaleReturn::METHOD_CASH ? $refundValue : null,
                'notes' => $data['notes'] ?? null,
                'status' => SaleReturn::STATUS_COMPLETED,
            ]);

            foreach ($lines as $line) {
                $return->items()->create([
                    'sale_item_id' => $line['sale_item']->id,
                    'quantity' => $line['quantity'],
                    'unit_subtotal' => $line['unit_subtotal'],
                    'unit_tax' => $line['unit_tax'],
                    'restock' => $line['restock'],
                ]);

                if ($line['restock']) {
                    $this->moveStock($sale, $line['sale_item']->product, $line['quantity'], "Devolución {$number}", $return);
                }
            }

            match ($method) {
                SaleReturn::METHOD_RECEIVABLE => $this->reduceDebt($sale, $refundValue),
                SaleReturn::METHOD_EXCHANGE => $this->applyExchange($return, $sale, $lines, $data),
                default => null,
            };

            return $return->fresh(['items.saleItem.product', 'exchangeSale']);
        });
    }

    /**
     * Anula una devolución y revierte stock y deuda. El efectivo entregado no se
     * toca: se arregla a mano, igual que se entregó.
     */
    public function void(SaleReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return = SaleReturn::lockForUpdate()->findOrFail($return->id);

            if ($return->isVoided()) {
                throw new Exception('Esta devolución ya está anulada.');
            }

            // La venta de un cambio nace en backoffice, y SaleService::cancel() nunca
            // permite anular una venta de backoffice — revertirla a mano aquí sería
            // saltarse ese guard.
            if ($return->exchange_sale_id) {
                throw new Exception("Esta devolución generó la venta {$return->exchangeSale->number} como cambio — no se puede anular.");
            }

            $sale = $return->sale;

            foreach ($return->items()->with('saleItem.product')->get() as $item) {
                $product = $item->saleItem->product;
                $quantity = (float) $item->quantity;

                if ($item->restock) {
                    $this->moveStock($sale, $product, -$quantity, "Anulación de devolución {$return->number}", $return);
                }

                // Cambio por el mismo producto: el reemplazo que salió vuelve a entrar.
                if ($return->refund_method === SaleReturn::METHOD_EXCHANGE) {
                    $this->moveStock($sale, $product, $quantity, "Anulación de devolución {$return->number} (reemplazo)", $return);
                }
            }

            if ($return->refund_method === SaleReturn::METHOD_RECEIVABLE && $sale->receivable) {
                $this->receivableService->increaseBalance($sale->receivable, (float) $return->refund_value);
            }

            $return->update([
                'status' => SaleReturn::STATUS_VOIDED,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
            ]);
        });
    }

    private function resolveMethod(Sale $sale, ?string $requested): string
    {
        if ($requested === SaleReturn::METHOD_EXCHANGE) {
            return SaleReturn::METHOD_EXCHANGE;
        }

        return $sale->payment_type === Sale::PAYMENT_CREDIT
            ? SaleReturn::METHOD_RECEIVABLE
            : SaleReturn::METHOD_CASH;
    }

    /**
     * Valida cada línea contra lo disponible y calcula su snapshot proporcional
     * (el descuento de la venta original ya está en subtotal).
     *
     * @return array<int, array{sale_item: SaleItem, quantity: float, restock: bool, unit_subtotal: float, unit_tax: float, total: float}>
     */
    private function buildLines(Sale $sale, array $rawLines): array
    {
        if (empty($rawLines)) {
            throw new Exception('Selecciona al menos un producto a devolver.');
        }

        $items = $sale->items()->with('product')->get()->keyBy('id');
        $lines = [];

        foreach ($rawLines as $raw) {
            /** @var SaleItem|null $saleItem */
            $saleItem = $items->get((int) ($raw['sale_item_id'] ?? 0));
            if (! $saleItem) {
                throw new Exception('Una de las líneas no pertenece a esta venta.');
            }

            if (isset($lines[$saleItem->id])) {
                throw new Exception('Una línea de la venta está repetida.');
            }

            $quantity = (float) ($raw['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new Exception('La cantidad a devolver debe ser mayor a cero.');
            }

            $available = $saleItem->returnableQuantity();
            if ($quantity > $available) {
                $name = $saleItem->product->name ?? 'este producto';
                throw new Exception('Solo quedan '.rtrim(rtrim(number_format($available, 2), '0'), '.')." unidad(es) de {$name} por devolver.");
            }

            $unitSubtotal = (float) $saleItem->subtotal / (float) $saleItem->quantity;
            $unitTax = (float) $saleItem->tax_amount / (float) $saleItem->quantity;

            $lines[$saleItem->id] = [
                'sale_item' => $saleItem,
                'quantity' => $quantity,
                'restock' => (bool) ($raw['restock'] ?? true),
                'unit_subtotal' => $unitSubtotal,
                'unit_tax' => $unitTax,
                'total' => round($unitSubtotal * $quantity, 2) + round($unitTax * $quantity, 2),
            ];
        }

        return array_values($lines);
    }

    private function reduceDebt(Sale $sale, float $amount): void
    {
        $receivable = $sale->receivable;

        if (! $receivable) {
            throw new Exception('Esta venta a crédito no tiene cuenta por cobrar asociada.');
        }

        $this->receivableService->reduceBalance($receivable, $amount);
    }

    /**
     * Cambio de producto — siempre de una sola línea (create() lo exige). Sin
     * reemplazo elegido ("el mismo producto"): sale otra unidad de lo mismo, sin
     * dinero. Con un producto distinto: el valor devuelto paga una venta nueva
     * (TipoPago "Devolución") y la diferencia se cobra en efectivo — o se entrega,
     * si el reemplazo vale menos. Un cambio nunca toca la CxC.
     */
    private function applyExchange(SaleReturn $return, Sale $sale, array $lines, array $data): void
    {
        $replacementId = (int) ($data['replacement_product_id'] ?? 0);
        $sameProduct = ! $replacementId
            || (count($lines) === 1 && $replacementId === $lines[0]['sale_item']->product_id);

        if ($sameProduct) {
            foreach ($lines as $line) {
                $this->moveStock($sale, $line['sale_item']->product, -$line['quantity'], "Reemplazo por devolución {$return->number}", $return);
            }

            return;
        }

        $replacement = Product::findOrFail($replacementId);
        $replacementQty = (float) ($data['replacement_quantity'] ?? 1);
        if ($replacementQty <= 0) {
            throw new Exception('La cantidad del producto de reemplazo debe ser mayor a cero.');
        }

        ['subtotal' => $lineSubtotal, 'total' => $newTotal] = $this->quoteReplacement($replacement, $replacementQty);

        $refundValue = (float) $return->refund_value;
        $creditApplied = min($refundValue, $newTotal);
        $cashDue = round($newTotal - $creditApplied, 2);
        $cashBack = round($refundValue - $creditApplied, 2);

        $devolucion = $this->tipoPago(TipoPago::DEVOLUCION);
        $payments = [];
        if ($creditApplied > 0) {
            $payments[] = ['tipo_pago_id' => $devolucion->id, 'amount' => $creditApplied, 'reference' => $return->number];
        }
        if ($cashDue > 0) {
            $payments[] = ['tipo_pago_id' => $this->tipoPago(TipoPago::EFECTIVO)->id, 'amount' => $cashDue];
        }

        $exchangeSale = $this->saleService->create([
            'client_id' => $sale->client_id,
            'warehouse_id' => $sale->warehouse_id,
            'total_amount' => $lineSubtotal,
            'discount_total' => 0,
            'payment_type' => Sale::PAYMENT_CASH,
            'tipo_pago_id' => count($payments) === 1 ? $payments[0]['tipo_pago_id'] : null,
            'items' => [[
                'product_id' => $replacement->id,
                'quantity' => $replacementQty,
                'price' => (float) $replacement->price,
            ]],
            'payments' => $payments,
        ]);

        $return->update([
            'exchange_sale_id' => $exchangeSale->id,
            'cash_amount' => $cashBack > 0 ? $cashBack : null,
        ]);
    }

    /**
     * Precio del reemplazo en un cambio: `price` actual sin descuento, impuestos
     * con el mismo cálculo por línea que SaleService::create(). Público porque el
     * modal lo usa para mostrar el resumen antes de confirmar.
     *
     * @return array{subtotal: float, tax: float, total: float}
     */
    public function quoteReplacement(Product $replacement, float $quantity): array
    {
        $subtotal = round($quantity * (float) $replacement->price, 2);
        $tax = (float) collect($replacement->taxes())
            ->sum(fn ($key) => round($subtotal * config("impuestos.{$key}.rate") / 100, 2));

        return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => round($subtotal + $tax, 2)];
    }

    /**
     * Movimiento de inventario con signo (TYPE_RETURN). Un servicio nunca mueve
     * stock, y con inventory.tracking apagado no se escribe nada (igual que la venta).
     */
    private function moveStock(Sale $sale, ?Product $product, float $signedQty, string $description, SaleReturn $return): void
    {
        if (! $product || $product->isService() || ! module_enabled('inventory.tracking')) {
            return;
        }

        $this->inventoryService->register([
            'warehouse_id' => $sale->warehouse_id,
            'product_id' => $product->id,
            'quantity' => $signedQty,
            'type' => InventoryMovement::TYPE_RETURN,
            'description' => $description,
            'reference_type' => SaleReturn::class,
            'reference_id' => $return->id,
        ]);
    }

    private function tipoPago(string $slug): TipoPago
    {
        return TipoPago::withTrashed()->where('slug', $slug)->first()
            ?? throw new Exception("Falta el método de pago \"{$slug}\" en esta instalación (TipoPagoSeeder).");
    }
}
