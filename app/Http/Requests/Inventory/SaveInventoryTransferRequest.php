<?php

namespace App\Http\Requests\Inventory;

use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear o editar una transferencia en borrador (v1.5.0 REQ-2.4). La existencia en el
 * origen se valida al enviar (todo o nada), no aquí: el borrador puede prepararse antes.
 */
class SaveInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory_transfers.create');
    }

    public function rules(): array
    {
        $activeWarehouse = Rule::exists('warehouses', 'id')->where('is_active', true);

        return [
            'from_warehouse_id' => ['required', $activeWarehouse],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', $activeWarehouse],
            'notes' => 'nullable|string|max:500',

            'lines' => 'required|array|min:1',
            'lines.*.product_id' => [
                'required', 'distinct',
                Rule::exists('products', 'id')->where('type', Product::TYPE_PRODUCT)->whereNull('deleted_at'),
            ],
            'lines.*.quantity' => 'required|numeric|gt:0',
            'lines.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'from_warehouse_id' => 'almacén de origen',
            'to_warehouse_id' => 'almacén de destino',
            'notes' => 'nota',
            'lines' => 'líneas',
            'lines.*.product_id' => 'producto',
            'lines.*.quantity' => 'cantidad',
            'lines.*.notes' => 'nota de la línea',
        ];
    }

    public function messages(): array
    {
        return [
            'to_warehouse_id.different' => 'El destino debe ser distinto del origen.',
            'lines.required' => 'Agrega al menos una línea.',
            'lines.*.product_id.distinct' => 'Este producto está repetido en otra línea.',
            'lines.*.product_id.exists' => 'Elige un producto (los servicios no se transfieren).',
        ];
    }
}
