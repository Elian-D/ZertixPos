<?php

namespace App\Http\Requests\Sales\Pos\PosConfig;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePosConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('pos_config.update');
    }

    /**
     * Prepara los datos antes de validar
     */
    protected function prepareForValidation()
    {
        // Convertir checkboxes ausentes en false
        $this->merge([
            'allow_quick_customer_creation' => $this->has('allow_quick_customer_creation'),
            'allow_quote_without_save' => $this->has('allow_quote_without_save'),
            'auto_print_receipt' => $this->has('auto_print_receipt'),
        ]);
    }

    public function rules(): array
    {
        return [
            'allow_quick_customer_creation' => 'boolean',
            'default_walkin_customer_id'    => 'required|exists:clients,id',
            'allow_quote_without_save'      => 'boolean',
            'auto_print_receipt'            => 'boolean',
            'receipt_size'                  => 'required|in:58mm,80mm',
            // v1.5.0 REQ-3.6: cuenta de banco por defecto de los pagos que no son efectivo.
            'default_financial_account_id'  => ['nullable', Rule::exists('financial_accounts', 'id')->where('type', 'bank')->where('is_active', true)],
        ];
    }

    public function attributes(): array
    {
        return [
            'default_walkin_customer_id' => 'Cliente por defecto',
            'receipt_size'               => 'Tamaño del recibo',
            'default_financial_account_id' => 'Cuenta de banco por defecto',
        ];
    }

    public function messages(): array
    {
        return [
            'default_walkin_customer_id.required' => 'Debes seleccionar un cliente por defecto.',
            'default_walkin_customer_id.exists' => 'El cliente seleccionado no existe.',
            'receipt_size.required' => 'Debes seleccionar un tamaño de recibo.',
        ];
    }
}