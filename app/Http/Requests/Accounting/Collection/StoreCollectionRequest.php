<?php

namespace App\Http\Requests\Accounting\Collection;

use App\Models\Accounting\Receivable;
use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Defensa en profundidad: la ruta ya está gateada por module:sales.receivables
        // (REQ-10.9 bis), pero si el flag se apaga entre el GET del form y el POST,
        // esto evita registrar un cobro contra un módulo que el negocio desactivó.
        return $this->user()->can('collections.create') && module_enabled('sales.receivables');
    }

    public function rules(): array
    {
        return [
            'receivable_id' => ['required', 'exists:receivables,id'],
            'tipo_pago_id' => ['required', 'exists:tipo_pagos,id'],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $receivable = Receivable::find($this->receivable_id);
                    if ($receivable && $value > $receivable->current_balance) {
                        $fail("El monto del cobro ($value) no puede ser mayor al saldo pendiente ({$receivable->current_balance}).");
                    }
                },
            ],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
