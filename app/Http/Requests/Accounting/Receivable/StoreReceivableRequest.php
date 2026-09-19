<?php

namespace App\Http\Requests\Accounting\Receivable;

use App\Models\Accounting\Receivable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receivables.create');
    }

    public function rules(): array
    {
        return [
            'client_id'         => 'required|exists:clients,id',
            'journal_entry_id'  => 'nullable|exists:journal_entries,id',
            'document_number'   => 'required|string|max:50|unique:receivables,document_number',
            'accounting_account_id' => [
                    'required',
                    'exists:accounting_accounts,id',
                    function ($attribute, $value, $fail) {
                        $account = \App\Models\Accounting\AccountingAccount::find($value);
                        // Validamos que el código empiece por 1.1.02
                        if ($account && !str_starts_with($account->code, '1.1.02')) {
                            $fail('La cuenta seleccionada debe ser una cuenta de tipo Cuentas por Cobrar.');
                        }
                    },
                ],
            'description'       => 'required|string|max:255',
            'total_amount'      => 'required|numeric|min:0.01',
            'emission_date'     => 'required|date',
            'due_date'          => 'required|date|after_or_equal:emission_date',
            'status'            => ['nullable', Rule::in(array_keys(Receivable::getStatuses()))],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'       => 'Debe seleccionar un cliente para asignar la deuda.',
            'document_number.unique'   => 'Este número de factura ya ha sido registrado previamente.',
            'total_amount.min'         => 'El monto de la deuda debe ser mayor a cero.',
            'due_date.after_or_equal'  => 'La fecha de vencimiento no puede ser anterior a la fecha de emisión.',
            'description.required'     => 'Debe indicar un concepto o descripción para esta cuenta por cobrar.'
        ];
    }
}