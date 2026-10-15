<?php

namespace App\Http\Requests\Purchases;

use App\Enums\TaxIdentifierType;
use App\Models\Purchases\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Crear o editar un proveedor (v1.5.0 REQ-4.1): las tres pestañas del formulario. */
class SaveVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post') ? 'vendors.create' : 'vendors.edit');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_id' => $this->tax_id ? preg_replace('/\D/', '', (string) $this->tax_id) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $vendor = $this->route('vendor');

        return [
            'business_name' => 'required|string|max:150',
            'trade_name' => 'nullable|string|max:150',
            'tax_identifier_type' => ['nullable', Rule::enum(TaxIdentifierType::class)],
            'tax_id' => [
                'nullable', 'required_with:tax_identifier_type', 'string', 'max:20',
                Rule::when(in_array($this->tax_identifier_type, ['RNC', 'CEDULA'], true), ['digits_between:9,11']),
                Rule::unique('vendors', 'tax_id')->ignore($vendor)->whereNull('deleted_at'),
            ],
            'contact_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'payment_condition' => ['required', Rule::in(array_keys(Vendor::getConditions()))],
            'credit_days' => 'nullable|required_if:payment_condition,credit|integer|min:1|max:365',
            'default_financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('is_active', true)],
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'business_name' => 'razón social',
            'trade_name' => 'nombre comercial',
            'tax_identifier_type' => 'tipo de documento',
            'tax_id' => 'RNC o cédula',
            'contact_name' => 'persona de contacto',
            'phone' => 'teléfono',
            'email' => 'correo',
            'address' => 'dirección',
            'payment_condition' => 'condición de pago',
            'credit_days' => 'días de crédito',
            'default_financial_account_id' => 'cuenta de pago sugerida',
            'notes' => 'notas',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.unique' => 'Ya hay un proveedor con este RNC o cédula.',
            'tax_id.digits_between' => 'El RNC tiene 9 dígitos y la cédula 11.',
            'credit_days.required_if' => 'Indica cuántos días de crédito da el proveedor.',
        ];
    }
}
