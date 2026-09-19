<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        // 'success', no 'status' (Breeze) — es la clave que docs/ui/toast.md
        // reconoce para disparar el toast automático al renderizar.
        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
