<?php

namespace App\Http\Requests\Accounting\Receivable;

use App\Models\Accounting\Receivable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receivables.edit');
    }

    public function rules(): array
    {
        $receivable = $this->route('receivable');
        $isPartiallyPaid = $receivable->current_balance < $receivable->total_amount;

        return [
            'client_id' => 'required|exists:clients,id',
            'accounting_account_id' => [
                'required',
                'exists:accounting_accounts,id',
                function ($attribute, $value, $fail) {
                    $account = \App\Models\Accounting\AccountingAccount::find($value);
                    // Validamos que sea del nodo de CxC (1.1.02)
                    if ($account && !str_starts_with($account->code, '1.1.02')) {
                        $fail('La cuenta seleccionada no es válida para Cuentas por Cobrar.');
                    }
                },
            ],
            'document_number' => ['required', 'string', 'max:50', Rule::unique('receivables')->ignore($receivable->id)],
            'description'     => 'required|string|max:255',
            'total_amount'    => [
                'required', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) use ($receivable, $isPartiallyPaid) {
                    if ($isPartiallyPaid && $value != $receivable->total_amount) {
                        $fail('No se puede modificar el monto total de una factura con abonos.');
                    }
                }
            ],
            'emission_date'   => 'required|date',
            'due_date'        => 'required|date|after_or_equal:emission_date',
            'status'          => ['required', Rule::in(array_keys(Receivable::getStatuses()))],
        ];
    }

    public function messages(): array
    {
        return [
            'total_amount.numeric'    => 'El monto debe ser un valor numérico válido.',
            'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión.',
            'status.in'               => 'El estado seleccionado no es válido.'
        ];
    }
}