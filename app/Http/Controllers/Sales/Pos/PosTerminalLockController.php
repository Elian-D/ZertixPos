<?php

namespace App\Http\Controllers\Sales\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sales\Pos\PosTerminal;
use Illuminate\Http\Request;

class PosTerminalLockController extends Controller
{
    /**
     * Verifica el PIN de una terminal vía AJAX. Usado por los modales de
     * apertura/cierre de sesión del backoffice (resources/views/sales/pos/sessions/partials),
     * que gestionan terminales directamente sin pasar por el Lobby.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
            'pin' => 'required|numeric|digits:4',
        ]);

        $terminal = PosTerminal::findOrFail($request->terminal_id);

        if (! $terminal->verifyPin($request->pin)) {
            return response()->json([
                'message' => 'PIN de terminal incorrecto.',
            ], 422);
        }

        session()->put("terminal_verified.{$terminal->id}", now()->timestamp);

        return response()->json([
            'message' => 'Acceso concedido.',
            'status' => 'success',
        ]);
    }

    /**
     * Bloquea la terminal: invalida la verificación de PIN en sesión y regresa al Lobby.
     * El Lobby es el único punto de entrada al PIN (ya no existe una pantalla de lock
     * dedicada) — al reintentar esta misma terminal, `CheckTerminalAccess` exigirá el
     * PIN de nuevo porque la sesión ya no tiene "terminal_verified" para este id.
     */
    public function lock(PosTerminal $pos_terminal)
    {
        session()->forget("terminal_verified.{$pos_terminal->id}");

        return redirect()
            ->route('sales.pos.index')
            ->with('info', "Terminal '{$pos_terminal->name}' bloqueada. Ingresa tu PIN para reanudarla.");
    }

    /**
     * Heartbeat de actividad (Fase 7.0/7.7). Vestigial desde que
     * CheckTerminalAccess dejó de expirar la verificación por inactividad
     * (docs/features/POS-Interfaz.md 9.0): ya no hay ventana que refrescar,
     * el `put` de abajo es un no-op funcional. Se deja sin quitar la ruta ni
     * el ping del Workspace (fuera de alcance de este cambio); es candidato
     * a eliminarse si no se le encuentra otro uso.
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
        ]);

        if (session()->has("terminal_verified.{$request->terminal_id}")) {
            session()->put("terminal_verified.{$request->terminal_id}", now()->timestamp);
        }

        return response()->json(['status' => 'ok']);
    }
}
