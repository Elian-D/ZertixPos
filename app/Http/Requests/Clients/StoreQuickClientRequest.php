<?php

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuickClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Usamos el mismo permiso de creación de clientes
        return $this->user()->can('clients create');
    }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'tax_id'                 => ['nullable', 'string', 'max:50', Rule::unique('clients')],
            'phone'                  => 'nullable|string|max:20',
            'email'                  => 'nullable|email|max:255',
            'address'                => 'nullable|string|max:500',
            // Estos campos son opcionales porque el DTO usará general_config() si vienen nulos
            'state_id'               => 'nullable|exists:states,id',
            'city'                   => 'nullable|string|max:100',
            'tax_identifier_type_id' => 'nullable|exists:tax_identifier_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'El nombre del cliente es obligatorio.',
            'tax_id.unique'  => 'Este RNC/Cédula ya está registrado.',
            'email.email'    => 'El formato del correo no es válido.',
        ];
    }
}