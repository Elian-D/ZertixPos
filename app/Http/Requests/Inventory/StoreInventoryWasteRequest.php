<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\InventoryWasteItem;
use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registrar una merma (v1.5.0 REQ-2.2): almacén, fecha, nota y líneas con motivo. El
 * stock insuficiente lo valida el servicio al aplicar (todo o nada).
 */
class StoreInventoryWasteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory_wastes.create');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'waste_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',

            'lines' => 'required|array|min:1',
            'lines.*.product_id' => [
                'required', 'distinct',
                Rule::exists('products', 'id')->where('type', Product::TYPE_PRODUCT)->whereNull('deleted_at'),
            ],
            'lines.*.quantity' => 'required|numeric|gt:0',
            'lines.*.reason' => ['required', Rule::in(array_keys(InventoryWasteItem::getReasons(manual: true)))],
            'lines.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'almacén',
            'waste_date' => 'fecha',
            'notes' => 'nota',
            'lines' => 'líneas',
            'lines.*.product_id' => 'producto',
            'lines.*.quantity' => 'cantidad',
            'lines.*.reason' => 'motivo',
            'lines.*.notes' => 'nota de la línea',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Agrega al menos una línea.',
            'lines.*.product_id.distinct' => 'Este producto está repetido en otra línea.',
            'lines.*.product_id.exists' => 'Elige un producto (los servicios no llevan inventario).',
            'waste_date.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}
