<?php

namespace App\Http\Requests\Purchases;

use App\Models\Products\Product;
use App\Models\Purchases\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Crear o editar una orden de compra en borrador (v1.5.0 REQ-5.1). */
class SavePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase_orders.create');
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'payment_condition' => ['required', Rule::in(array_keys(PurchaseOrder::getConditions()))],
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('is_active', true)],
            'notes' => 'nullable|string|max:500',

            'lines' => 'required|array|min:1',
            'lines.*.product_id' => [
                'required', 'distinct',
                Rule::exists('products', 'id')->where('type', Product::TYPE_PRODUCT)->whereNull('deleted_at'),
            ],
            'lines.*.quantity' => 'required|numeric|gt:0',
            'lines.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'vendor_id' => 'proveedor',
            'warehouse_id' => 'almacén',
            'order_date' => 'fecha',
            'expected_date' => 'entrega estimada',
            'payment_condition' => 'condición de pago',
            'financial_account_id' => 'cuenta de pago',
            'notes' => 'nota',
            'lines' => 'líneas',
            'lines.*.product_id' => 'producto',
            'lines.*.quantity' => 'cantidad',
            'lines.*.unit_cost' => 'costo unitario',
        ];
    }

    public function messages(): array
    {
        return [
            'expected_date.after_or_equal' => 'La entrega estimada no puede ser antes de la fecha de la orden.',
            'lines.required' => 'Agrega al menos una línea.',
            'lines.*.product_id.distinct' => 'Este producto está repetido en otra línea.',
            'lines.*.product_id.exists' => 'Elige un producto (los servicios no se compran para inventario).',
        ];
    }
}
