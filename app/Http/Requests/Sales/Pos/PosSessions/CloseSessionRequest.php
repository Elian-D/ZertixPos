<?php

namespace App\Http\Requests\Sales\Pos\PosSessions;

use App\Models\Sales\Pos\PosSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pos sessions manage');
    }

    public function rules(): array
    {
        return [
            'closing_balance'   => ['required', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'difference_reason' => ['nullable', Rule::in(array_keys(PosSession::getReasons()))],
            'difference_notes'  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'closing_balance.required' => 'Debe ingresar el monto total contado en caja.',
            'closing_balance.min'      => 'El monto de cierre no puede ser negativo.',
        ];
    }

    /**
     * Exige motivo de descuadre solo si el arqueo no cuadra — no bloquea el
     * cierre, pero sí obliga a dejar constancia de por qué hay diferencia.
     * Recalcula el esperado aquí (no confía en nada enviado por el cliente)
     * usando la misma sesión resuelta por el route model binding.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            /** @var PosSession $session */
            $session = $this->route('pos_session');
            $expected = $session->calculateExpected();
            $difference = (float) $this->closing_balance - $expected;

            if (abs($difference) < 0.01) {
                return;
            }

            if (! $this->filled('difference_reason')) {
                $validator->errors()->add('difference_reason', 'Debe seleccionar un motivo para el descuadre.');

                return;
            }

            if ($this->difference_reason === PosSession::REASON_OTRO && ! $this->filled('difference_notes')) {
                $validator->errors()->add('difference_notes', 'Explique el motivo del descuadre.');
            }
        });
    }
}
