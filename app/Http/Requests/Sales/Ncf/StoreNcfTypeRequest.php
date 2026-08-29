<?php

namespace App\Http\Requests\Sales\Ncf;

use Illuminate\Foundation\Http\FormRequest;

class StoreNcfTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ncf_types.manage');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'prefix'        => ['required', 'string', 'size:1'], // B o E
            'code'          => ['required', 'string', 'size:2', 'unique:ncf_types,code'],
            'is_electronic' => ['required', 'boolean'],
            'requires_rnc'  => ['required', 'boolean'],
            'is_active'     => ['boolean'],
        ];
    }
}