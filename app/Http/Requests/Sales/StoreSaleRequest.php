<?php

namespace App\Http\Requests\Sales;

use App\Models\Clients\Client;
use App\Models\Inventory\InventoryStock;
use App\Models\Products\Product;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Sales\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create sales');
    }

    /**
     * Cuando la venta viene del Workspace POS (ruta con {pos_terminal}), el almacén
     * siempre es el de la terminal y el frontend no lo envía en el payload.
     * Lo resolvemos aquí para que pase la validación `required` antes de llegar
     * al controlador, que además lo vuelve a fijar como fuente de verdad.
     */
    protected function prepareForValidation(): void
    {
        $terminal = $this->route('pos_terminal');

        if ($terminal && ! $this->filled('warehouse_id')) {
            $this->merge(['warehouse_id' => $terminal->warehouse_id]);
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],

            // Nullable siempre: aunque el sistema tenga NCF activo, el cajero puede
            // despachar "Sin Comprobante" (venta interna sin impacto fiscal). La
            // obligatoriedad ya no es un switch global todo-o-nada; se decide por venta.
            'ncf_type_id' => ['nullable', 'exists:ncf_types,id'],

            // RNC capturado en el momento (cliente sin tax_id en archivo) para Crédito Fiscal.
            'client_rnc' => ['nullable', 'string', 'max:20'],

            'sale_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:today'],
            // Con sales.receivables apagado (núcleo flexible, REQ-10.5) el form deja de
            // ofrecer 'credit' — no solo se oculta el botón, la validación en sí lo rechaza.
            'payment_type' => [
                'required',
                Rule::in(module_enabled('sales.receivables') ? [Sale::PAYMENT_CASH, Sale::PAYMENT_CREDIT] : [Sale::PAYMENT_CASH]),
            ],
            'tipo_pago_id' => [
                Rule::requiredIf($this->payment_type === Sale::PAYMENT_CASH),
                'nullable',
                'exists:tipo_pagos,id',
            ],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'cash_change' => ['nullable', 'numeric', 'min:0'],
            // Referencia libre (últimos 4 dígitos, # de confirmación, etc.) para métodos
            // no-efectivo. Obligatoriedad real evaluada en withValidator() según el método.
            'payment_reference' => ['nullable', 'string', 'max:100'],

            // Pago dividido (multi-método): si viene presente, ES la fuente de verdad del
            // cobro y reemplaza tipo_pago_id/cash_received/payment_reference de arriba —
            // ver SaleService::processPayments(), que ya sabía manejar esto desde antes.
            'payments' => ['nullable', 'array'],
            'payments.*.tipo_pago_id' => ['required_with:payments', 'exists:tipo_pagos,id'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],

            // FASE 5: Reglas de totales y descuentos
            'total_amount' => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            // FASE 5: Reglas de descuentos por ítem
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],

            // Contexto POS (opcional; solo presente cuando la venta se origina en una terminal)
            'pos_terminal_id' => ['nullable', 'exists:pos_terminals,id'],
            'is_walkin_customer' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $client = Client::find($this->client_id);

            // --- VALIDACIÓN DE NCF ---
            // Solo se valida si REALMENTE se pidió un comprobante (ncf_type_id presente).
            // "Sin Comprobante" (ncf_type_id vacío) no pasa por aquí, sin importar el módulo.
            if (module_enabled('sales.ncf') && $this->ncf_type_id) {
                $ncfType = NcfType::find($this->ncf_type_id);
                if ($ncfType && $ncfType->requires_rnc && $client) {
                    // El RNC es válido si ya está en el cliente O si se acaba de capturar en esta venta.
                    $hasRnc = ! empty($client->tax_id) || ! empty($this->client_rnc);
                    if (! $hasRnc) {
                        $validator->errors()->add('ncf_type_id', "El tipo {$ncfType->nombre} requiere un RNC/Cédula.");
                    }
                }
            }

            // --- VALIDACIÓN DE TIPO DE PAGO ---
            if ($this->payment_type === Sale::PAYMENT_CASH && empty($this->tipo_pago_id)) {
                $validator->errors()->add('tipo_pago_id', 'Debe seleccionar un método de pago para ventas al contado.');
            }

            // Referencia (últimos dígitos, # de autorización, # de cheque) es SIEMPRE
            // opcional, nunca bloquea el envío (Fase 6, REQ-6.9) — Efectivo no la
            // necesita, Tarjeta ya viene verificada por el datáfono antes de llegar
            // acá, y el resto (transferencia/depósito/cheque) se gestiona por fuera
            // del sistema; no es algo que deba forzarse bajo presión de caja.
            $payments = $this->input('payments', []);

            // --- CÁLCULO DE TOTALES, IMPUESTOS Y VALIDACIÓN DE STOCK ---
            $subtotalBruto = 0;
            $descuentoTotalCalculado = 0;
            $taxAccum = 0;

            // Precargado en una sola query (evita N+1): un "servicio" (is_stockable=false)
            // no tiene por qué tener nunca una fila de InventoryStock, así que se salta el
            // chequeo de stock por completo en vez de rechazar la venta con "insuficiente".
            $products = Product::whereIn('id', collect($this->items)->pluck('product_id'))
                ->get(['id', 'is_stockable'])
                ->keyBy('id');

            // Impuestos por producto (Fase 5, REQ-5.3) — mismo cálculo multi-tasa que
            // SaleService::create(), para que la validación de acá nunca diverja de lo
            // que realmente se persiste.
            $productTaxes = DB::table('product_taxes')
                ->whereIn('product_id', collect($this->items)->pluck('product_id'))
                ->get()
                ->groupBy('product_id');

            foreach ($this->items as $index => $item) {
                // Matemáticas del ítem
                $itemBruto = ($item['quantity'] * $item['price']);
                $itemDescuento = $item['discount_amount'] ?? 0;

                $subtotalBruto += $itemBruto;
                $descuentoTotalCalculado += $itemDescuento;

                $itemNeto = $itemBruto - $itemDescuento;
                $taxKeys = $productTaxes->get($item['product_id'], collect())->pluck('tax_key');
                $taxAccum += $taxKeys->sum(fn ($key) => round($itemNeto * config("impuestos.{$key}.rate", 0) / 100, 2));

                // Stock: solo aplica a productos físicos, y solo con inventory.tracking
                // activo (núcleo flexible, REQ-10.5/10.9) — apagado, InventoryStock nunca
                // se actualiza (queda congelado), así que validar contra ese número
                // rechazaría ventas reales por un dato que ya no significa nada. Si el
                // producto no está en el catálogo cargado (no debería pasar, ya se validó
                // `exists` arriba) se valida igual, por seguridad.
                $isStockable = $products->get($item['product_id'])?->is_stockable ?? true;

                if ($isStockable && module_enabled('inventory.tracking')) {
                    $stock = InventoryStock::where('warehouse_id', $this->warehouse_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (! $stock || $stock->quantity < $item['quantity']) {
                        $available = $stock ? $stock->quantity : 0;
                        $validator->errors()->add("items.{$index}.quantity", "Stock insuficiente. Disponible: {$available}.");
                    }
                }
            }

            // --- VALIDACIÓN DE INTEGRIDAD MATEMÁTICA ---
            // 1. Validamos que el total_amount enviado corresponda al BRUTO real (suma de precio * cantidad)
            if (abs($subtotalBruto - $this->total_amount) > 0.01) {
                $validator->errors()->add('total_amount', 'El monto bruto no coincide con la suma de los productos.');
            }

            // 2. Validamos que el descuento global enviado no sea falso o manipulado
            if (abs($descuentoTotalCalculado - ($this->discount_total ?? 0)) > 0.01) {
                $validator->errors()->add('discount_total', 'El descuento reportado no coincide con la suma de descuentos aplicados.');
            }

            // 3. Calculamos el Neto real a cobrar (Bruto - Descuento + impuestos por línea)
            $subtotalNeto = $subtotalBruto - $descuentoTotalCalculado;
            $totalFinalNeto = $subtotalNeto + $taxAccum;

            // --- VALIDACIÓN DE EFECTIVO / PAGO DIVIDIDO ---
            if ($this->payment_type === Sale::PAYMENT_CASH && ! empty($payments)) {
                // Pago dividido: a diferencia del pago único (que permite recibir de más y dar
                // vuelto), cada línea es exactamente lo que se aplica a la venta — deben sumar
                // el total exacto, ni de más ni de menos.
                $sumaPagos = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));
                if (abs($sumaPagos - $totalFinalNeto) > 0.01) {
                    $validator->errors()->add('payments', 'La suma de los pagos ('.number_format($sumaPagos, 2).') no coincide con el total a cobrar ('.number_format($totalFinalNeto, 2).').');
                }
            } elseif ($this->payment_type === Sale::PAYMENT_CASH) {
                $recibido = (float) $this->cash_received;
                $totalCobrar = (float) $totalFinalNeto; // <-- CAMBIO AQUÍ

                if ($recibido < $totalCobrar) {
                    $validator->errors()->add('cash_received', 'El efectivo recibido es menor al total neto a pagar.');
                }
            }

            // --- LÓGICA DE CRÉDITO ---
            if ($this->payment_type === Sale::PAYMENT_CREDIT && $client) {
                if ($client->id == 1 || $client->name === 'Consumidor Final') {
                    $validator->errors()->add('payment_type', 'El Consumidor Final no puede procesar ventas a crédito.');
                }

                // Antes: ->estadoCliente->category->code (relación inexistente, siempre
                // null) — el bloqueo de crédito nunca se disparaba en el servidor, solo
                // había un botón deshabilitado en el frontend (Fase 11, REQ-11.6).
                if ($client->esMoroso()) {
                    $validator->errors()->add('client_id', 'Crédito denegado: el cliente tiene facturas vencidas pendientes de pago.');
                }

                // <-- CAMBIO AQUÍ: Sumamos el Neto al balance actual
                $nuevoSaldoProyectado = $client->balance + $totalFinalNeto;
                if ($nuevoSaldoProyectado > $client->credit_limit) {
                    $disponible = number_format($client->credit_limit - $client->balance, 2);
                    $validator->errors()->add('total_amount', "Límite de crédito superado. Disponible: \${$disponible}.");
                }
            }
        });
    }
}
