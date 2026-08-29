<?php

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('equipment.edit');
    }

    public function rules(): array
    {
        return [
            'point_of_sale_id'  => 'required|exists:point_of_sales,id',
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'serial_number'     => 'nullable|string|max:100',
            'name'              => 'nullable|string|max:100',
            'model'             => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'active'            => 'boolean',
        ];
    }
}
