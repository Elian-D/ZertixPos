<?php

namespace App\Http\Requests\Sales\Quotes;

;

use App\Models\Sales\Quotes\Quote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quotes.edit');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:clients,id'],
            'expires_at'  => ['required', 'date', 'after_or_equal:today'],
            'notes'       => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:draft,approved,cancelled'], // No permite 'converted' vía update manual
            
            'subtotal'       => ['required', 'numeric', 'min:0'],
            'total'          => ['required', 'numeric', 'min:0'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quote = $this->route('quote'); // Asumiendo binding en la ruta

            if ($quote->status === Quote::STATUS_CONVERTED) {
                $validator->errors()->add('status', 'No se puede editar una cotización que ya ha sido convertida en venta.');
            }

            if ($quote->status === Quote::STATUS_EXPIRED && !$this->user()->can('quotes.edit')) {
                $validator->errors()->add('status', 'No tiene permisos para reactivar una cotización vencida.');
            }
        });
    }
}