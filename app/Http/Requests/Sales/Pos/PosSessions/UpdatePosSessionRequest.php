<?php

namespace App\Http\Requests\Sales\Pos\PosSessions;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pos sessions manage');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:500'],
            // Se pueden agregar más campos si permites corregir saldos (con precaución)
        ];
    }
}