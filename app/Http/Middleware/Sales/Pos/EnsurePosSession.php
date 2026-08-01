<?php

namespace App\Http\Middleware\Sales\Pos;

use Closure;
use Illuminate\Http\Request;
use App\Models\Sales\Pos\PosSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Buscamos si el usuario actual tiene una sesión abierta
        $session = PosSession::where('user_id', Auth::id())
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        if (!$session) {
            // Si no tiene sesión, lo mandamos a la vista de "Apertura de Caja"
            // Nota: Debes tener una ruta con este nombre o ajustar el redirect
            return redirect()->route('pos.sessions.index')
                ->with('warning', 'Debes abrir una sesión de caja para acceder al terminal de ventas.');
        }

        // 2. Opcional: Compartir la sesión en el request para que el controlador no tenga que buscarla de nuevo
        $request->merge(['pos_session' => $session]);

        return $next($request);
    }
}