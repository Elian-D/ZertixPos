<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Mismas reglas que traía `PasswordController::update()` inline (Breeze
 * stock) — se convierte a FormRequest solo para poder agregar el guard de
 * cuenta demo (REQ-3.9, v1.3.0 Fase 3) siguiendo el patrón del proyecto
 * (CLAUDE.md: "FormRequest classes handle both validation and permission
 * checks"), no como parte de la migración completa del perfil (REQ-7.6,
 * todavía Pendiente).
 */
class UpdatePasswordRequest extends FormRequest
{
    /**
     * La vista (`profile/partials/update-password-form.blade.php`) lee
     * `$errors->updatePassword->get(...)` — mismo bag que ya usaba el
     * `$request->validateWithBag('updatePassword', ...)` original, antes de
     * convertir esto a FormRequest.
     */
    protected $errorBag = 'updatePassword';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * REQ-3.9 — mismo bloqueo server-side que ProfileUpdateRequest, mismo
     * motivo: el tenant demo es compartido, nadie debería poder tomar el
     * control de la cuenta cambiándole la contraseña.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        if (! tenant()?->is_demo) {
            return;
        }

        $validator->errors()->add(
            'password',
            'Esta es una cuenta de demostración compartida — no se puede cambiar la contraseña.',
        );
    }
}
