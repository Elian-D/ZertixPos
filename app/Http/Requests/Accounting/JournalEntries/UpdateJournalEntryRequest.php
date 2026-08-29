<?php

namespace App\Http\Requests\Accounting\JournalEntries;

use App\Models\Accounting\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El usuario debe tener permiso y el asiento debe estar en borrador para ser editable
        $entry = $this->route('journal_entry');
        return $this->user()->can('journal_entries.edit') && $entry->status === JournalEntry::STATUS_DRAFT;
    }

    public function rules(): array
    {
        return [
            'entry_date'  => 'required|date',
            'reference'   => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            
            'items'                         => 'required|array|min:2',
            'items.*.accounting_account_id' => 'required|exists:accounting_accounts,id|distinct',
            'items.*.debit'                 => 'required|numeric|min:0',
            'items.*.credit'                => 'required|numeric|min:0',
            'items.*.note'                  => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe agregar al menos dos movimientos al asiento.',
            'items.min' => 'Un asiento contable debe tener al menos dos movimientos (Partida Doble).',
            'items.*.accounting_account_id.required' => 'La cuenta contable es obligatoria.',
            'items.*.accounting_account_id.distinct' => 'Has repetido la cuenta contable en varias líneas. Por favor, unifícalas.',
            'items.*.debit.numeric' => 'El débito debe ser un número.',
            'items.*.credit.numeric' => 'El crédito debe ser un número.',
        ];
    }
}