<?php

namespace App\Http\Requests\Sales\Ncf;

use Illuminate\Foundation\Http\FormRequest;

class StoreNcfSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ncf_sequences.manage');
    }

    public function rules(): array
    {
        $type = \App\Models\Sales\Ncf\NcfType::find($this->ncf_type_id);
        $maxLimit = ($type && $type->is_electronic) ? 9999999999 : 99999999;

        return [
            'ncf_type_id'     => ['required', 'exists:ncf_types,id'],
            'from'            => ['required', 'numeric', 'min:1', "max:$maxLimit"],
            'to'              => ['required', 'numeric', 'gt:from', "max:$maxLimit"],
            'expiry_date'     => ['required', 'date', 'after:today'],
            'alert_threshold' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.gt' => 'El número final debe ser mayor al número inicial.',
            'expiry_date.after' => 'La fecha de vencimiento debe ser una fecha futura.',
        ];
    }
}