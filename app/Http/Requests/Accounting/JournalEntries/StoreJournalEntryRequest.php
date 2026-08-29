<?php

namespace App\Http\Requests\Accounting\JournalEntries;

use App\Models\Accounting\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('journal_entries.create');
    }

    public function rules(): array
    {
        return [
            'entry_date'  => 'required|date',
            'reference'   => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            'status'      => ['nullable', Rule::in(array_keys(JournalEntry::getStatuses()))],
            
            // Validación del Detalle (Items)
            'items'                         => 'required|array|min:2', // Al menos 2 líneas para partida doble
            'items.*.accounting_account_id' => 'required|exists:accounting_accounts,id|distinct',
            'items.*.debit'                 => 'required|numeric|min:0',
            'items.*.credit'                => 'required|numeric|min:0',
            'items.*.note'                  => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.accounting_account_id.distinct' => 'No puedes seleccionar la misma cuenta contable más de una vez en el mismo asiento.',
            'items.min' => 'Un asiento contable debe tener al menos dos movimientos (Débito y Crédito).',
            'items.*.accounting_account_id.exists' => 'Una de las cuentas seleccionadas no es válida.',
        ];
    }
}