<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pagar una cuenta por pagar (v1.5.0 REQ-4.3). Que no supere lo pendiente ni deje la cuenta
 * en negativo lo valida el servicio con las filas bloqueadas.
 */
class PayPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payables.pay');
    }

    public function rules(): array
    {
        return [
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('is_active', true)],
            'amount' => 'required|numeric|gt:0|max:9999999999.99',
            'payment_date' => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'financial_account_id' => 'cuenta',
            'amount' => 'monto',
            'payment_date' => 'fecha',
            'reference' => 'referencia',
            'notes' => 'nota',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_date.before_or_equal' => 'La fecha no puede ser futura.',
            'financial_account_id.exists' => 'Elige una cuenta activa.',
        ];
    }
}
