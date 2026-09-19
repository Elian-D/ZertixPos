<?php

namespace App\Http\Requests\Sales\Quotes;

use App\Models\Sales\Quote;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quotes.create');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:clients,id'],
            'expires_at'  => ['required', 'date', 'after_or_equal:today'],
            'notes'       => ['nullable', 'string', 'max:500'],
            'origin'      => ['required', 'string', 'in:backoffice,pos'],
            
            // Totales (Validación de integridad)
            'subtotal'       => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'total'          => ['required', 'numeric', 'min:0'],

            // Items
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'exists:products,id'],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
            'items.*.price'             => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount'   => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) return;

            $subtotalCalculated = 0;
            foreach ($this->items as $item) {
                $subtotalCalculated += ($item['quantity'] * $item['price']);
            }

            // Validar que el total enviado coincida con el cálculo (margen de error 0.01)
            $expectedTotal = $subtotalCalculated - ($this->discount_total ?? 0);
            if (abs($expectedTotal - $this->total) > 0.01) {
                $validator->errors()->add('total', 'La suma de los ítems no coincide con el total de la cotización.');
            }
        });
    }
}