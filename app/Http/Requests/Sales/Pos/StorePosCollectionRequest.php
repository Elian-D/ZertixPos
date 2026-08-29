<?php

namespace App\Http\Requests\Sales\Pos;

use App\Models\Accounting\Receivable;
use Illuminate\Foundation\Http\FormRequest;

class StorePosCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Mismo permiso que gatea todo el flujo del Workspace (Lobby/Checkout) — un
        // Cobro desde el TPV es una operación de caja más, no del backoffice de
        // Cuentas por Cobrar (que usa 'collections.create', ver routes/app/finance.php).
        return $this->user()->can('pos_sessions.manage') && module_enabled('sales.receivables');
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
                        $fail("El monto del cobro ({$value}) no puede ser mayor al saldo pendiente ({$receivable->current_balance}).");
                    }
                },
            ],
            // Siempre opcional, nunca bloquea el envío (Fase 6, REQ-6.9) — Efectivo no
            // la necesita, Tarjeta ya viene verificada por el datáfono, y el resto se
            // gestiona por fuera del sistema.
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
