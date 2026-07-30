<?php

namespace App\Http\Middleware\Sales\Pos;

use App\Models\Sales\Pos\PosTerminal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTerminalAccess
{
    /**
     * Maneja la verificación del PIN de la terminal y su expiración.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obtener la terminal (asumimos que el parámetro en la ruta es 'pos_terminal' o viene en el request)
        $terminal = $request->route('pos_terminal') ?? PosTerminal::find($request->terminal_id);

        if (! $terminal) {
            return $next($request); // Si no hay terminal en el contexto, seguimos
        }

        // 2. Si la terminal no requiere PIN (o no tiene uno configurado), permitimos el paso
        if (! $terminal->requiresPinVerification()) {
            return $next($request);
        }

        // 3. Verificar si existe la verificación en la sesión
        $sessionKey = "terminal_verified.{$terminal->id}";
        $lastVerified = session()->get($sessionKey);

        if (! $lastVerified) {
            return $this->redirectToPin();
        }

        // 4. Lógica de expiración (30 minutos de inactividad)
        $minutesPassed = (now()->timestamp - $lastVerified) / 60;

        if ($minutesPassed > 30) {
            session()->forget($sessionKey);

            return $this->redirectToPin();
        }

        // 5. Actualizar el timestamp para "renovar" los 30 minutos por actividad
        session()->put($sessionKey, now()->timestamp);

        return $next($request);
    }

    /**
     * Redirección al Lobby (único punto donde se pide el PIN) o respuesta JSON según el tipo de request.
     */
    private function redirectToPin()
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Sesión de terminal expirada.',
                'require_pin' => true,
            ], 403);
        }

        return redirect()
            ->route('sales.pos.index')
            ->with('info', 'La sesión de la terminal expiró o fue bloqueada. Ingresa tu PIN para continuar.');
    }
}
