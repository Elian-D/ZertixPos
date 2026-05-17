<?php

namespace App\Http\Requests\Sales;

use App\Models\Sales\Sale;
use App\Models\Inventory\InventoryStock;
use App\Models\Clients\Client;
use App\Models\Sales\Ncf\NcfType; 
use App\Models\Configuration\ConfiguracionGeneral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create sales');
    }

    public function rules(): array
    {
        $config = general_config();
        $usaNcf = $config?->usa_ncf ?? false;

        return [
            'client_id'    => ['required', 'exists:clients,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            
            'ncf_type_id'  => [
                $usaNcf ? 'required' : 'nullable', 
                'exists:ncf_types,id'
            ], 
            
            'sale_date'    => ['required', 'date', 'after_or_equal:today', 'before_or_equal:today'],
            'payment_type' => ['required', Rule::in([Sale::PAYMENT_CASH, Sale::PAYMENT_CREDIT])],
            'tipo_pago_id' => [
                Rule::requiredIf($this->payment_type === Sale::PAYMENT_CASH), 
                'nullable', 
                'exists:tipo_pagos,id'
            ],
            'cash_received'  => ['nullable', 'numeric', 'min:0'],
            'cash_change'    => ['nullable', 'numeric', 'min:0'],
            
            // FASE 5: Reglas de totales y descuentos
            'total_amount'   => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'], 
            'apply_tax'      => ['nullable', 'boolean'],
            'notes'          => ['nullable', 'string', 'max:255'],

            'items'                       => ['required', 'array', 'min:1'],
            'items.*.product_id'          => ['required', 'exists:products,id'],
            'items.*.quantity'            => ['required', 'numeric', 'min:0.01'],
            'items.*.price'               => ['required', 'numeric', 'min:0'],
            // FASE 5: Reglas de descuentos por ítem
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount'     => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $client = Client::with('estadoCliente.categoria')->find($this->client_id);
            $config = general_config();

            // --- VALIDACIÓN DE NCF ---
            if ($config?->usa_ncf && $this->ncf_type_id) {
                $ncfType = NcfType::find($this->ncf_type_id);
                if ($ncfType && $client) {
                    if (in_array($ncfType->code, ['01', '31']) && empty($client->tax_id)) {
                        $validator->errors()->add('ncf_type_id', "El tipo {$ncfType->nombre} requiere que el cliente tenga un RNC/Cédula.");
                    }
                }
            }

            // --- VALIDACIÓN DE TIPO DE PAGO ---
            if ($this->payment_type === Sale::PAYMENT_CASH && empty($this->tipo_pago_id)) {
                $validator->errors()->add('tipo_pago_id', 'Debe seleccionar un método de pago para ventas al contado.');
            }

            // --- CÁLCULO DE TOTALES Y VALIDACIÓN DE STOCK ---
            $subtotalBruto = 0;
            $descuentoTotalCalculado = 0;

            foreach ($this->items as $index => $item) {
                // Matemáticas del ítem
                $itemBruto = ($item['quantity'] * $item['price']);
                $itemDescuento = $item['discount_amount'] ?? 0;
                
                $subtotalBruto += $itemBruto;
                $descuentoTotalCalculado += $itemDescuento;

                // Stock
                $stock = InventoryStock::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$stock || $stock->quantity < $item['quantity']) {
                    $available = $stock ? $stock->quantity : 0;
                    $validator->errors()->add("items.{$index}.quantity", "Stock insuficiente. Disponible: {$available}.");
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

            // 3. Calculamos el Neto real a cobrar (Bruto - Descuento + ITBIS)
            $subtotalNeto = $subtotalBruto - $descuentoTotalCalculado;
            $totalFinalNeto = $subtotalNeto;

            if ($this->boolean('apply_tax')) {
                $taxRate = general_config()->impuesto->valor ?? 0;
                $taxAmount = $subtotalNeto * ($taxRate / 100);
                $totalFinalNeto = $subtotalNeto + $taxAmount;
            }
            
            // --- VALIDACIÓN DE EFECTIVO ---
            if ($this->payment_type === Sale::PAYMENT_CASH) {
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

                $categoryCode = $client->estadoCliente->category->code ?? null;
                if (in_array($categoryCode, ['BLOQUEO_TOTAL', 'FINANCIERO_RESTRICTO'])) {
                    $validator->errors()->add('client_id', "Crédito denegado: El cliente tiene un estado de {$client->estadoCliente->nombre}.");
                }

                // <-- CAMBIO AQUÍ: Sumamos el Neto al balance actual
                $nuevoSaldoProyectado = $client->balance + $totalFinalNeto; 
                if ($nuevoSaldoProyectado > $client->credit_limit) {
                    $disponible = number_format($client->credit_limit - $client->balance, 2);
                    $validator->errors()->add('total_amount', "Límite de crédito superado. Disponible: \${$disponible}.");
                }
            }

            // --- LÓGICA DE CRÉDITO ---
            if ($this->payment_type === Sale::PAYMENT_CREDIT && $client) {
                if ($client->id == 1 || $client->name === 'Consumidor Final') {
                    $validator->errors()->add('payment_type', 'El Consumidor Final no puede procesar ventas a crédito.');
                }

                $categoryCode = $client->estadoCliente->category->code ?? null;
                if (in_array($categoryCode, ['BLOQUEO_TOTAL', 'FINANCIERO_RESTRICTO'])) {
                    $validator->errors()->add('client_id', "Crédito denegado: El cliente tiene un estado de {$client->estadoCliente->nombre}.");
                }

                $nuevoSaldoProyectado = $client->balance + $this->total_amount;
                if ($nuevoSaldoProyectado > $client->credit_limit) {
                    $disponible = number_format($client->credit_limit - $client->balance, 2);
                    $validator->errors()->add('total_amount', "Límite de crédito superado. Disponible: \${$disponible}.");
                }
            }
        });
    }
}