<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * REQ-3.9, v1.3.0 Fase 3 — el tenant demo es compartido entre todo el que
     * lo visite (`demo.zertixpos.com`), no la cuenta privada de nadie. Bloqueo
     * server-side, no solo ocultar el botón en la UI — un POST armado a mano
     * (mismo criterio que REQ-2.7) no debe poder cambiar el email real.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        if (! tenant()?->is_demo) {
            return;
        }

        if ($this->input('email') !== $this->user()->email) {
            $validator->errors()->add(
                'email',
                'Esta es una cuenta de demostración compartida — no se puede cambiar el correo.',
            );
        }
    }
}
