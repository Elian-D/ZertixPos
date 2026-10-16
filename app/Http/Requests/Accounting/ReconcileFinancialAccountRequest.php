<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\FinancialReconciliation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vista "Conciliar" (v1.5.0 REQ-3.3). El motivo es obligatorio solo si hay diferencia
 * contra el kardex, y eso lo compara el servicio con la cuenta bloqueada.
 */
class ReconcileFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financial_accounts.reconcile');
    }

    public function rules(): array
    {
        return [
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('is_active', true)],
            'real_balance' => 'required|numeric|min:0|max:9999999999.99',
            'reconciled_at' => 'required|date|before_or_equal:today',
            'reason' => ['nullable', Rule::in(array_keys(FinancialReconciliation::getReasons()))],
            'notes' => 'nullable|required_if:reason,'.FinancialReconciliation::REASON_OTHER.'|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'financial_account_id' => 'cuenta',
            'real_balance' => 'saldo real',
            'reconciled_at' => 'fecha',
            'reason' => 'motivo',
            'notes' => 'nota',
        ];
    }

    public function messages(): array
    {
        return [
            'financial_account_id.exists' => 'Elige una cuenta activa.',
            'reconciled_at.before_or_equal' => 'La fecha no puede ser futura.',
            'notes.required_if' => 'Explica el motivo en la nota.',
        ];
    }
}
