<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Crear una toma física (v1.5.0 REQ-2.1): almacén, alcance, conteo ciego y notas. */
class StoreInventoryCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory_counts.create');
    }

    protected function prepareForValidation(): void
    {
        // Alcance "todo el almacén" → sin categoría.
        if ($this->input('scope') !== 'category') {
            $this->merge(['category_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'scope' => ['required', Rule::in(['all', 'category'])],
            'category_id' => ['nullable', 'required_if:scope,category', Rule::exists('categories', 'id')],
            'blind' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'almacén',
            'scope' => 'alcance',
            'category_id' => 'categoría',
            'notes' => 'notas',
        ];
    }

    public function messages(): array
    {
        return ['category_id.required_if' => 'Elige la categoría a contar.'];
    }
}
