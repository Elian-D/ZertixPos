<?php

namespace App\Http\Requests\Accounting\DocumentTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create document types');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:document_types,name',
            'code' => 'required|string|max:10|unique:document_types,code',
            'prefix' => 'nullable|string|max:5',
            'current_number' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tipo de documento es obligatorio.',
            'name.unique' => 'Ya existe un documento con este nombre.',
            'code.required' => 'La sigla o código (Ej: FAC) es obligatoria.',
            'code.unique' => 'Este código ya está asignado a otro tipo de documento.',
            'current_number.min' => 'El correlativo inicial no puede ser negativo.',
        ];
    }
}
