<?php

namespace App\Http\Requests\Sales\Ncf;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNcfTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ncf_types.manage');
    }


    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'requires_rnc' => ['nullable', 'boolean'], // Cambiado a nullable
            'is_active'    => ['nullable', 'boolean'], // Cambiado a nullable
        ];
    }

    protected function prepareForValidation()
    {
        // Esto asegura que si el checkbox no viene, se valide como 0
        $this->merge([
            'requires_rnc' => $this->has('requires_rnc'),
            'is_active'    => $this->has('is_active'),
        ]);
    }
}