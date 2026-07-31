<?php

namespace App\Http\Middleware\Sales\Pos;

use App\Models\Sales\Pos\PosTerminal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTerminalAccess
{
    /**
     * Verifica que el PIN de la terminal haya sido validado en esta sesión de navegador.
     *
     * Ya no expira a los 30 min de inactividad (decisión explícita): el PIN de terminal
     * es un secreto compartido por caja, no por cajero — igual que se documentó en
     * docs/features/POS-Interfaz.md 9.3 para el cierre de turno, forzar reingresarlo cada
     * cierto tiempo no confirma que la persona correcta siga frente a la terminal, solo
     * interrumpe a quien sí está autorizado a estar ahí. La verificación dura mientras
     * dure la sesión del navegador (o hasta que `PosTerminalLockController::lock()` la
     * invalide explícitamente, ej. al hacer clic en "Bloquear").
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
        if (! session()->has("terminal_verified.{$terminal->id}")) {
            return $this->redirectToPin();
        }

        return $next($request);
    }

    /**
     * Redirección al Lobby (único punto donde se pide el PIN) o respuesta JSON según el tipo de request.
     */
    private function redirectToPin()
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Verificación de terminal requerida.',
                'require_pin' => true,
            ], 403);
        }

        return redirect()
            ->route('sales.pos.index')
            ->with('info', 'Esta terminal está bloqueada. Ingresa tu PIN para continuar.');
    }
}
