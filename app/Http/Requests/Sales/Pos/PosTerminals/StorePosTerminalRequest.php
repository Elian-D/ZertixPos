<?php

namespace App\Http\Requests\Sales\Pos\PosTerminals;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create pos terminals');
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:100|unique:pos_terminals,name',
            'warehouse_id'        => 'required|exists:warehouses,id',
            'default_ncf_type_id' => 'nullable|exists:ncf_types,id',
            'default_client_id'   => 'nullable|exists:clients,id',
            'is_mobile'           => 'boolean',
            'printer_format'      => 'nullable|in:80mm,58mm',
            'is_active'           => 'boolean',
            'requires_pin'        => 'boolean',
            // PIN obligatorio solo si requires_pin es true
            'access_pin'          => 'required_if:requires_pin,true|nullable|numeric|digits:4',

            // 11.2/11.2.5: política de descuentos 100% por terminal, sin fallback global,
            // con topes separados por ítem y por global (un solo tope no puede distinguir
            // "descuento propio de la línea" de "porción del global repartida sobre ella").
            // Obligatorios desde que se crea la terminal (el formulario precarga los
            // defaults de la migración: true/true/5.00/10.00). `discount_policy` no es
            // campo de formulario — queda fijo en 'exclusion' (único valor operativo).
            'allow_item_discount'              => 'required|boolean',
            'allow_global_discount'             => 'required|boolean',
            'max_item_discount_percentage'      => 'required|numeric|min:0|max:100',
            'max_global_discount_percentage'    => 'required|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'El nombre de la caja/terminal es obligatorio.',
            'name.unique'              => 'Ya existe una terminal con este nombre.',
            'warehouse_id.required'    => 'Debe asignar un almacén para el descuento de stock.',
            'cash_account_id.required' => 'Debe vincular una cuenta contable de caja.',
            'printer_format.in'        => 'El formato seleccionado no es válido (80mm o 58mm).',
            'access_pin.required'      => 'Es obligatorio definir un PIN de acceso de 4 dígitos.',
            'access_pin.numeric'       => 'El PIN debe ser solo números.',
            'access_pin.digits'        => 'El PIN debe tener exactamente 4 dígitos.',
            'max_item_discount_percentage.required'   => 'El límite de descuento por ítem de esta terminal es obligatorio.',
            'max_item_discount_percentage.min'        => 'El porcentaje debe ser al menos 0.',
            'max_item_discount_percentage.max'        => 'El porcentaje no puede superar 100.',
            'max_global_discount_percentage.required' => 'El límite de descuento global de esta terminal es obligatorio.',
            'max_global_discount_percentage.min'      => 'El porcentaje debe ser al menos 0.',
            'max_global_discount_percentage.max'      => 'El porcentaje no puede superar 100.',
        ];
    }
}