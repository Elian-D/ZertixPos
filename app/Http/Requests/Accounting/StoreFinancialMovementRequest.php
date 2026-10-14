<?php

namespace App\Http\Requests\Accounting;

use App\Services\Accounting\Treasury\FinancialAccountService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vista "Nuevo movimiento" (v1.5.0 REQ-3.2): depósito, retiro o transferencia. La cuenta
 * de destino solo se pide al transferir. El saldo suficiente lo valida el servicio con la
 * cuenta bloqueada.
 */
class StoreFinancialMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financial_accounts.move');
    }

    public function rules(): array
    {
        $active = Rule::exists('financial_accounts', 'id')->where('is_active', true);

        return [
            'financial_account_id' => ['required', $active],
            'type' => ['required', Rule::in(array_keys(FinancialAccountService::getMoveTypes()))],
            'to_account_id' => [
                'exclude_unless:type,'.FinancialAccountService::MOVE_TRANSFER,
                'required', $active, 'different:financial_account_id',
            ],
            'amount' => 'required|numeric|gt:0|max:9999999999.99',
            'movement_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'financial_account_id' => 'cuenta',
            'type' => 'tipo',
            'to_account_id' => 'cuenta de destino',
            'amount' => 'monto',
            'movement_date' => 'fecha',
            'notes' => 'nota',
        ];
    }

    public function messages(): array
    {
        return [
            'movement_date.before_or_equal' => 'La fecha no puede ser futura.',
            'to_account_id.different' => 'Elige una cuenta distinta a la de origen.',
            'financial_account_id.exists' => 'Elige una cuenta activa.',
            'to_account_id.exists' => 'Elige una cuenta activa.',
        ];
    }
}
