<?php

namespace App\Http\Requests\Sales\Invoices;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Sales\Invoice;
use Illuminate\Validation\Rule;

class IndexInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('invoices.view');
    }

    public function rules(): array
    {
        return [
            'client_id'   => ['nullable', 'exists:clients,id'],
            'type'        => ['nullable', Rule::in(['cash', 'credit'])],
            'status'      => ['nullable', Rule::in([Invoice::STATUS_ACTIVE, Invoice::STATUS_CANCELLED])],
            'format_type' => ['nullable', Rule::in([Invoice::FORMAT_TICKET, Invoice::FORMAT_LETTER, Invoice::FORMAT_ROUTE])],
            'from_date'   => ['nullable', 'date'],
            'to_date'     => ['nullable', 'date', 'after_or_equal:from_date'],
            'search'      => ['nullable', 'string', 'max:100'],
        ];
    }
}