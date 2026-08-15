<?php

namespace App\Http\Requests\Accounting\DocumentTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit document types');
    }

    public function rules(): array
    {
        $type = $this->route('document_type');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('document_types')->ignore($type->id),
            ],
            'code' => ['nullable', 'string', 'max:10', Rule::unique('document_types', 'code')->ignore($type->id)],
            'prefix' => 'nullable|string|max:5',
            'current_number' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'El nombre ya está siendo utilizado por otro documento.',
            'code.unique' => 'Este código ya pertenece a otro registro.',
            'current_number.required' => 'Debe definir el número actual del correlativo.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->route('document_type');

            // 1. Proteger 'code' de los tipos que el sistema consulta por texto.
            $changingCode = $this->filled('code') && $this->input('code') !== $type->code;
            if ($changingCode && $type->isSystemProtected()) {
                $validator->errors()->add(
                    'code',
                    'No se puede modificar el código de un tipo de documento que el sistema usa internamente.'
                );
            }

            // 2. Bloquear el correlativo una vez que ya se emitió al menos un documento con este tipo.
            $changingNumber = $this->filled('current_number')
                && (int) $this->current_number !== $type->current_number;

            if ($changingNumber && $type->hasIssuedDocuments()) {
                $validator->errors()->add(
                    'current_number',
                    'No se puede modificar el correlativo: ya existen documentos emitidos con este tipo. Cambiarlo comprometería la integridad de la numeración y los NCF ya emitidos.'
                );
            }
        });
    }
}
