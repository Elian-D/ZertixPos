<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear o editar una cuenta de Cajas y Bancos (v1.5.0 REQ-3.1). El saldo inicial solo
 * se pide al crear: después, el saldo solo cambia con movimientos.
 */
class SaveFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financial_accounts.manage');
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('financial_accounts', 'name')->ignore($this->route('account')),
            ],
            'type' => ['required', Rule::in(array_keys(FinancialAccount::getTypes()))],
            'bank_name' => 'nullable|string|max:100',
            'last_four_digits' => 'nullable|digits:4',
            'notes' => 'nullable|string|max:500',
            'opening_balance' => [$creating ? 'required' : 'exclude', 'numeric', 'min:0', 'max:9999999999.99'],
            'opening_date' => [$creating ? 'required' : 'exclude', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'bank_name' => 'banco',
            'last_four_digits' => 'últimos 4 dígitos',
            'notes' => 'nota',
            'opening_balance' => 'saldo de hoy',
            'opening_date' => 'fecha',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya hay una cuenta con este nombre.',
            'last_four_digits.digits' => 'Escribe solo los últimos 4 dígitos de la cuenta.',
            'opening_date.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}
