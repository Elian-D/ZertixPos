<?php

namespace App\Http\Requests\Sales\Pos\PosTerminals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePosTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit pos terminals');
    }

    public function rules(): array
    {
        $terminal = $this->route('pos_terminal');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pos_terminals')->ignore($terminal->id)
            ],
            'warehouse_id'        => 'required|exists:warehouses,id',
            'default_ncf_type_id' => 'nullable|exists:ncf_types,id',
            'default_client_id'   => 'nullable|exists:clients,id',
            'is_mobile'           => 'boolean',
            'printer_format'      => 'nullable|in:80mm,58mm', 
            'is_active'           => 'boolean',
            'requires_pin' => 'boolean',
            // Fase 6, REQ-6.5 — habilita el botón "Cobrar Deudas" en el Workspace de
            // esta terminal.
            'allow_receivable_collection' => 'boolean',
            'access_pin'   => [
                'nullable',
                'numeric',
                'digits:4',
                // Obligatorio si se activa 'requires_pin' y la terminal NO tiene un PIN previo
                Rule::requiredIf(function () use ($terminal) {
                    return $this->requires_pin && is_null($terminal->access_pin);
                }),
            ],

            // 11.2/11.2.5: política de descuentos 100% por terminal, sin fallback global,
            // con topes separados por ítem y por global.
            'allow_item_discount'              => 'required|boolean',
            'allow_global_discount'             => 'required|boolean',
            // El % solo es obligatorio si su toggle está activo — el input se deshabilita
            // en el form cuando el toggle está apagado (Fase 6 UI, rediseño Stitch), y un
            // input disabled no se envía, así que exigirlo siempre rompía "desactivar
            // descuentos" con un error de validación falso.
            'max_item_discount_percentage'      => ['nullable', 'required_if:allow_item_discount,1', 'numeric', 'min:0', 'max:100'],
            'max_global_discount_percentage'    => ['nullable', 'required_if:allow_global_discount,1', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_item_discount_percentage.required_if'   => 'El límite de descuento por ítem de esta terminal es obligatorio.',
            'max_item_discount_percentage.min'           => 'El porcentaje debe ser al menos 0.',
            'max_item_discount_percentage.max'           => 'El porcentaje no puede superar 100.',
            'max_global_discount_percentage.required_if' => 'El límite de descuento global de esta terminal es obligatorio.',
            'max_global_discount_percentage.min'         => 'El porcentaje debe ser al menos 0.',
            'max_global_discount_percentage.max'         => 'El porcentaje no puede superar 100.',
        ];
    }
}