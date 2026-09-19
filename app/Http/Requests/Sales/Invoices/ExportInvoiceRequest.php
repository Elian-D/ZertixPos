<?php

namespace App\Http\Requests\Sales\Invoices;

class ExportInvoiceRequest extends IndexInvoiceRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('invoices.export');
    }
}