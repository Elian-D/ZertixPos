<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('warehouses.manage');
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'type'        => ['required', 'string', Rule::in(array_keys(Warehouse::getTypes()))],
            'address'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $warehouse = $this->route('warehouse');
            $nuevoEstado = $this->boolean('is_active');

            // Si el almacén está activo y se intenta desactivar
            if ($warehouse->is_active && !$nuevoEstado) {
                $activos = Warehouse::where('is_active', true)->count();
                if ($activos <= 1) {
                    $validator->errors()->add('is_active', 'Operación denegada: El sistema requiere al menos un almacén activo para operar el inventario.');
                }
            }
        });
    }
}