<?php

namespace App\Http\Requests\Clients;

use App\Enums\TaxIdentifierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Usamos el permiso del Seeder: 'clients edit'
        return $this->user()->can('clients edit');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['individual', 'company'])],
            'name' => 'required|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'provincia_id' => 'required|exists:provinces,id',
            'municipio_id' => 'nullable|exists:municipalities,id',
            'address' => 'nullable|string|max:500',
            'tax_identifier_type' => ['required', Rule::enum(TaxIdentifierType::class)],
            'tax_id' => ['required', 'string', 'max:50', Rule::unique('clients')->ignore($this->client)],

            // 👇 Nuevos campos financieros
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms' => 'required|integer|min:0',
            'accounting_account_id' => 'nullable|exists:accounting_accounts,id',
            'create_accounting_account' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.unique' => 'Este identificador fiscal ya está registrado en el sistema.',
            'credit_limit.min' => 'El límite de crédito no puede ser un número negativo.',
            'payment_terms.integer' => 'Los términos de pago deben ser un número de días válido.',
            'accounting_account_id.exists' => 'La cuenta contable seleccionada no es válida.',
            'tax_identifier_type.required' => 'El tipo de documento fiscal es obligatorio.',
        ];
    }
}
