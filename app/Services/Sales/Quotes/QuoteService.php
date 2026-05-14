<?php

namespace App\Services\Sales\Quotes;

use App\Models\Sales\Quotes\{Quote, QuoteItem};
use App\Models\Products\Product;
use App\Services\Sales\SalesServices\SaleService;
use App\DTOs\Sales\PosContext;
use Illuminate\Support\Facades\{DB, Auth};
use Carbon\Carbon;
use Exception;

class QuoteService
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    /**
     * Crea una cotización recalculando precios desde la DB para seguridad.
     */
    public function store(array $data): Quote
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            $discountTotal = 0;

            // 1. Recálculo real del lado del servidor
            $itemsToSave = [];
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Usamos el precio del item (o el del producto si no viene)
                $price = $item['price'] ?? $product->sale_price;
                $qty = $item['quantity'];
                
                // Cálculo de descuentos
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemSubtotal = ($price * $qty) - $itemDiscount;

                $subtotal += ($price * $qty);
                $discountTotal += $itemDiscount;

                $itemsToSave[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'discount_amount' => $itemDiscount,
                    'discount_percentage' => ($itemDiscount > 0) ? ($itemDiscount / ($price * $qty)) * 100 : 0,
                    'subtotal' => $itemSubtotal
                ];
            }

            // 2. Crear cabecera
            $quote = Quote::create([
                'customer_id'     => $data['client_id'],
                'user_id'         => Auth::id(),
                'pos_terminal_id' => $data['pos_terminal_id'] ?? null,
                'pos_session_id'  => $data['pos_session_id'] ?? null,
                'origin'          => $data['origin'] ?? Quote::ORIGIN_BACKOFFICE,
                'status'          => Quote::STATUS_DRAFT,
                'subtotal'        => $subtotal,
                'discount_total'  => $discountTotal,
                'total'           => $subtotal - $discountTotal,
                'expires_at'      => $data['expires_at'] ?? now()->addDays(15),
                'notes'           => $data['notes'] ?? null,
            ]);

            // 3. Crear items snapshot
            foreach ($itemsToSave as $itemData) {
                $quote->items()->create($itemData);
            }

            return $quote;
        });
    }

    public function update(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data) {
            // 1. Limpieza de items previos (Snapshot)
            $quote->items()->delete();
            
            $subtotal = 0;
            $discountTotal = 0;
            $itemsToSave = [];

            // 2. Recálculo igual que en el store() por seguridad
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $item['price'] ?? $product->sale_price;
                $qty = (float)$item['quantity'];
                $discount = (float)($item['discount_amount'] ?? 0);

                $subtotal += ($price * $qty);
                $discountTotal += $discount;

                $itemsToSave[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'discount_amount' => $discount,
                    'discount_percentage' => ($discount > 0) ? ($discount / ($price * $qty)) * 100 : 0,
                    'subtotal' => ($price * $qty) - $discount
                ];
            }

            // 3. Actualizar Cabecera
            $quote->update([
                'customer_id'    => $data['client_id'],
                'notes'          => $data['notes'] ?? $quote->notes,
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'total'          => $subtotal - $discountTotal,
                // Podrías actualizar expires_at si lo permites en el form
            ]);

            // 4. Guardar nuevos items
            foreach ($itemsToSave as $itemData) {
                $quote->items()->create($itemData);
            }

            return $quote;
        });
    }

    /**
     * Convierte una cotización en una venta real usando SaleService
     */
    public function convertToSale(Quote $quote, array $additionalData = []): \App\Models\Sales\Sale
    {
        // 1. Validaciones de Estado
        if ($quote->status === Quote::STATUS_CONVERTED) {
            throw new Exception("Esta cotización ya fue convertida en venta.");
        }

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            $quote->update(['status' => Quote::STATUS_EXPIRED]);
            throw new Exception("La cotización ha expirado y no puede convertirse.");
        }

        return DB::transaction(function () use ($quote, $additionalData) {
            // 2. Preparar Contexto POS (si aplica)
            $context = null;
            if ($quote->origin === Quote::ORIGIN_POS || $quote->pos_session_id) {
                $context = new PosContext(
                    terminal_id:     $quote->pos_terminal_id,
                    session_id:      $quote->pos_session_id,
                    warehouse_id:    $quote->terminal?->warehouse_id ?? $additionalData['warehouse_id'],
                    cash_account_id: $quote->terminal?->cash_account_id
                );
            }

            // 3. Mapear Items al formato del SaleService
            // Nota: Pasamos 'price' como 'price' porque el SaleService lo mapea internamente a 'unit_price'
            $saleItems = $quote->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price, // El precio pactado en la cotización
                ];
            })->toArray();

            // 4. Consolidar payload para SaleService
            $saleData = [
                'client_id'      => $quote->customer_id,
                'warehouse_id'   => $quote->terminal?->warehouse_id ?? $additionalData['warehouse_id'],
                'total_amount'   => $quote->total,
                'items'          => $saleItems,
                'payment_type'   => $additionalData['payment_type'] ?? 'cash',
                'tipo_pago_id'   => $additionalData['tipo_pago_id'] ?? null,
                'ncf_type_id'    => $additionalData['ncf_type_id'] ?? null,
                'sale_date'      => now(),
            ];

            // 5. Ejecutar Venta
            $sale = $this->saleService->create($saleData, $context);

            // 6. Actualizar Cotización
            $quote->update([
                'status'  => Quote::STATUS_CONVERTED,
                'sale_id' => $sale->id
            ]);

            return $sale;
        });
    }
}