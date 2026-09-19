<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('warehouses.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:warehouses,name'],
            'type' => ['required', 'string', Rule::in(array_keys(Warehouse::getTypes()))],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del almacén es obligatorio para el registro contable.',
            'name.unique' => 'Ya existe un almacén con ese nombre.',
            'type.in' => 'El tipo de almacén seleccionado no es válido.',
        ];
    }
}
