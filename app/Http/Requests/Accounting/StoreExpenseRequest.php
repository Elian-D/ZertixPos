<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registrar un gasto (v1.5.0 REQ-4.4). De contado pide la cuenta (el saldo suficiente lo
 * valida el servicio con la cuenta bloqueada); a crédito, el vencimiento.
 */
class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expenses.create');
    }

    public function rules(): array
    {
        $cash = Expense::CONDITION_CASH;
        $credit = Expense::CONDITION_CREDIT;

        return [
            'category' => ['required', Rule::in(array_keys(Expense::getCategories()))],
            'concept' => 'required|string|max:150',
            'vendor_id' => [
                'nullable',
                Rule::exists('vendors', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'amount' => 'required|numeric|gt:0|max:9999999999.99',
            'expense_date' => 'required|date|before_or_equal:today',
            'payment_condition' => ['required', Rule::in([$cash, $credit])],
            'financial_account_id' => [
                "exclude_unless:payment_condition,{$cash}", 'required',
                Rule::exists('financial_accounts', 'id')->where('is_active', true),
            ],
            'due_date' => ["exclude_unless:payment_condition,{$credit}", 'required', 'date', 'after_or_equal:expense_date'],
            'supplier_invoice' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'categoría',
            'concept' => 'concepto',
            'vendor_id' => 'proveedor',
            'amount' => 'monto',
            'expense_date' => 'fecha',
            'payment_condition' => 'condición',
            'financial_account_id' => 'cuenta',
            'due_date' => 'vencimiento',
            'supplier_invoice' => 'factura o NCF',
            'notes' => 'nota',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_date.before_or_equal' => 'La fecha no puede ser futura.',
            'due_date.after_or_equal' => 'El vencimiento no puede ser antes de la fecha del gasto.',
            'financial_account_id.required' => 'Elige de qué cuenta sale el dinero.',
        ];
    }
}
