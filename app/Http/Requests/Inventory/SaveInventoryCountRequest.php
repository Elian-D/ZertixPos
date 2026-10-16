<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guardar avance del conteo (v1.5.0 REQ-2.1). Las cantidades llegan empaquetadas en un
 * solo campo JSON (`counts` = {item_id: cantidad|null}) en vez de un input por producto:
 * con cientos de productos, un input por línea choca con el max_input_vars de PHP
 * (1000 por defecto) y se perderían cantidades sin aviso.
 */
class SaveInventoryCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory_counts.count');
    }

    protected function prepareForValidation(): void
    {
        $decoded = json_decode((string) $this->input('counts', ''), true);
        $this->merge(['counts' => is_array($decoded) ? $decoded : null]);
    }

    public function rules(): array
    {
        return [
            'counts' => 'present|array',
            'counts.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return ['counts.*' => 'cantidad contada', 'notes' => 'notas'];
    }
}
