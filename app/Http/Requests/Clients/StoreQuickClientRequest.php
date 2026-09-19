<?php

namespace App\Http\Requests\Clients;

use App\Enums\TaxIdentifierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuickClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Usamos el mismo permiso de creación de clientes
        return $this->user()->can('clients.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'tax_id' => ['nullable', 'string', 'max:50', Rule::unique('clients')],
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            // Estos campos son opcionales porque el DTO usará general_config() si vienen nulos
            'provincia_id' => 'nullable|exists:provinces,id',
            'municipio_id' => 'nullable|exists:municipalities,id',
            'tax_identifier_type' => ['nullable', Rule::enum(TaxIdentifierType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'tax_id.unique' => 'Este RNC/Cédula ya está registrado.',
            'email.email' => 'El formato del correo no es válido.',
        ];
    }
}
