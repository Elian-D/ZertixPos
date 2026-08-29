<?php

namespace App\Http\Requests\PointOfSale;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePointOfSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delivery_points.edit');
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'business_type_id' => 'required|exists:business_types,id',
            'name' => 'required|string|max:150',
            'provincia_id' => 'required|exists:provinces,id',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'active' => 'boolean',
        ];
    }
}
