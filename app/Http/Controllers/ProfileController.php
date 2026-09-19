<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * REQ-7.6 — migrado del scaffolding de Breeze (inglés, sin `x-ui.*`, sin
 * mostrar rol/avatar) al mockup de Stitch ("Perfil de Usuario - ZertixPOS").
 * `destroy()` se eliminó a propósito, no es un descuido: el auto-borrado de
 * cuenta (Breeze stock) permitía que cualquier usuario logueado —un cajero,
 * cualquiera— se borrara a sí mismo del sistema sin pasar por un admin. En
 * ZertixPOS las cuentas las gestiona el dueño/admin desde el módulo de
 * Usuarios (REQ-2.7); acá ya no hay ninguna acción de borrado.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.index', [
            'user' => $request->user(),
            // SESSION_DRIVER=redis en este proyecto (.env) — la tabla `sessions`
            // nunca se llena, así que no hay "iniciada hace X" confiable sin
            // inspeccionar Redis directamente. Se muestra solo la IP real del
            // request actual, dato que sí es cierto sin importar el driver.
            'currentIp' => $request->ip(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        // 'success' es la clave que docs/ui/toast.md reconoce para disparar
        // el toast automático al renderizar — 'status' (lo que traía Breeze)
        // no es una de las claves que x-ui.toasts escucha, así que nunca
        // mostraba nada.
        return Redirect::route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
    }
}
