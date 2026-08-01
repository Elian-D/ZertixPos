<?php

namespace App\Http\Requests\Sales\Pos\PosSessions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Usamos el permiso que definimos en el seeder
        return $this->user()->can('pos sessions manage');
    }

    public function rules(): array
    {
        return [
            'terminal_id'     => ['required', Rule::exists('pos_terminals', 'id')->where('is_active', true)],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'terminal_id.required'     => 'Debe seleccionar una terminal para abrir el turno.',
            'terminal_id.exists'       => 'La terminal seleccionada no está activa.',
            'opening_balance.required' => 'El monto inicial de caja es obligatorio (puede ser 0).',
            'opening_balance.min'      => 'El monto inicial no puede ser un valor negativo.',
        ];
    }
}