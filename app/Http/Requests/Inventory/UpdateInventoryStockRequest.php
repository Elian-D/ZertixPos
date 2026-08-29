<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso debe estar definido en tu seeder
        return $this->user()->can('inventory_stocks.update');
    }

    public function rules(): array
    {
        return [
            // Validamos que el stock mínimo sea numérico y no negativo
            'min_stock' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'min_stock.required' => 'El stock mínimo es obligatorio.',
            'min_stock.numeric'  => 'El stock mínimo debe ser un número.',
            'min_stock.min'      => 'El stock mínimo no puede ser menor a cero.',
        ];
    }
}