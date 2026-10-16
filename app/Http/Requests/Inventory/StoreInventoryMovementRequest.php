<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajuste manual de inventario (v1.5.0 REQ-1.5): almacén, motivo, comentario y líneas
 * de entrada o salida. El stock insuficiente lo valida el servicio al aplicar.
 */
class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory_movements.create_adjustment');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'reason' => ['required', Rule::in(array_keys(InventoryMovement::getAdjustmentReasons()))],
            'notes' => 'required|string|min:5|max:200',

            'lines' => 'required|array|min:1',
            'lines.*.product_id' => [
                'required', 'distinct',
                Rule::exists('products', 'id')->where('type', Product::TYPE_PRODUCT)->whereNull('deleted_at'),
            ],
            'lines.*.direction' => ['required', Rule::in(['in', 'out'])],
            'lines.*.quantity' => 'required|numeric|gt:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'almacén',
            'reason' => 'motivo',
            'notes' => 'comentario',
            'lines' => 'líneas',
            'lines.*.product_id' => 'producto',
            'lines.*.direction' => 'tipo',
            'lines.*.quantity' => 'cantidad',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Agrega al menos una línea.',
            'lines.*.product_id.distinct' => 'Este producto está repetido en otra línea.',
            'lines.*.product_id.exists' => 'Elige un producto (los servicios no llevan inventario).',
        ];
    }
}
