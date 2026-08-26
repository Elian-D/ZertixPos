<?php

namespace App\Livewire\Sales\Pos\Pages;

use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PosTerminalLobby extends Component
{
    public ?int $selectedTerminalId = null;

    public string $pin = '';

    public string $opening_balance = '0.00';

    public string $notes = '';

    protected $listeners = ['refreshLobby' => '$refresh'];

    public function selectTerminal(int $terminalId)
    {
        $this->resetErrorBag();
        $this->pin = '';
        $this->selectedTerminalId = $terminalId;

        // El permiso de operar POS ya lo exige la ruta ('permission:pos sessions manage'
        // en routes/app/sales.php) — quien llega aquí ya está autorizado. Este check
        // es solo defensa en profundidad para la llamada Livewire en sí (que no pasa por el
        // middleware de la ruta de página), por eso es un 403 silencioso, no un mensaje en
        // la vista: la vista ya no necesita saber nada de permisos.
        abort_unless(Auth::user()->can('pos sessions manage'), 403);

        $terminal = PosTerminal::find($terminalId);
        if (! $terminal || ! $terminal->is_active) {
            $this->addError('terminal', 'La terminal seleccionada no está disponible.');

            return;
        }

        // Un turno activo ya no bloquea al cajero por ser "otro usuario": cualquiera con
        // permiso para operar sesiones POS puede continuar un turno ya abierto por otro
        // (ej. el que abrió la caja envía a un vendedor a atender).

        // Si la terminal no tiene PIN de acceso configurado, saltamos la verificación
        if (! $terminal->requiresPinVerification()) {
            $this->markTerminalVerified($terminal->id);

            return $this->proceedToWorkspaceOrOpening($terminal);
        }

        // Si ya está verificada en esta sesión de navegador (CheckTerminalAccess ya no la
        // expira por inactividad) no volvemos a pedir el PIN. Bloquear (PosTerminalLockController)
        // sí borra esta marca explícitamente, así que tras un bloqueo real esto no aplica.
        if (session()->has("terminal_verified.{$terminal->id}")) {
            return $this->proceedToWorkspaceOrOpening($terminal);
        }

        // Disparar de forma reactiva la apertura del modal de PIN vía eventos globales de Breeze
        $this->dispatch('open-modal', 'pos-pin-modal');
    }

    public function verifyPin()
    {
        $this->validate([
            'pin' => 'required|string|digits:4',
        ], [
            'pin.required' => 'El PIN es obligatorio.',
            'pin.digits' => 'El PIN debe ser de 4 números.',
        ]);

        $terminal = PosTerminal::find($this->selectedTerminalId);

        if (! $terminal || ! $terminal->verifyPin($this->pin)) {
            $this->addError('pin', 'El PIN de acceso de la terminal es incorrecto.');

            return;
        }

        // Cerramos el modal de PIN de inmediato
        $this->dispatch('close-modal', 'pos-pin-modal');

        $this->markTerminalVerified($terminal->id);
        $this->proceedToWorkspaceOrOpening($terminal);
    }

    /**
     * Registra la verificación en sesión para que el middleware de la
     * terminal (check.terminal.access) no vuelva a pedir el PIN al entrar al workspace.
     */
    protected function markTerminalVerified(int $terminalId): void
    {
        session()->put("terminal_verified.{$terminalId}", now()->timestamp);
    }

    protected function proceedToWorkspaceOrOpening(PosTerminal $terminal)
    {
        // Choke point real: aunque selectTerminal()/openSession() ya validan el permiso
        // para dar buen feedback en la UI, este método es el que de verdad decide si se
        // entra al Workspace o se abre un turno — se re-valida aquí por si se invoca un
        // método de Livewire directamente (ej. verifyPin) sin pasar por selectTerminal().
        abort_unless(Auth::user()->can('pos sessions manage'), 403);

        $activeSession = $terminal->sessions()->where('status', PosSession::STATUS_OPEN)->first();

        if ($activeSession) {
            // Reanudación: cualquier cajero autorizado retoma el turno ya abierto,
            // sin importar quién lo abrió originalmente (ya se validó el permiso arriba).
            return redirect()->route('sales.pos.workspace', $terminal->id);
        } else {
            // Requiere apertura de caja nueva: disparamos el evento para levantar el modal de balance
            $this->opening_balance = '0.00';
            $this->notes = '';
            $this->dispatch('open-modal', 'pos-opening-modal');
        }
    }

    public function openSession()
    {
        abort_unless(Auth::user()->can('pos sessions manage'), 403);

        $this->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ], [
            'opening_balance.required' => 'El fondo de apertura es obligatorio.',
            'opening_balance.numeric' => 'Debe ingresar un valor numérico válido.',
        ]);

        $terminal = PosTerminal::find($this->selectedTerminalId);
        $exists = $terminal->sessions()->where('status', PosSession::STATUS_OPEN)->exists();

        if ($exists) {
            session()->flash('error', 'Esta terminal acaba de ser abierta por otro turno.');

            return redirect()->route('pos.index');
        }

        PosSession::create([
            'terminal_id' => $this->selectedTerminalId,
            'user_id' => Auth::id(),
            'opened_by_user_id' => Auth::id(),
            'status' => PosSession::STATUS_OPEN,
            'opened_at' => now(),
            'opening_balance' => $this->opening_balance,
            'notes' => $this->notes,
        ]);

        $this->dispatch('close-modal', 'pos-opening-modal');

        return redirect()->route('sales.pos.workspace', $this->selectedTerminalId);
    }

    public function render()
    {
        $terminals = PosTerminal::where('is_active', true)
            ->with(['warehouse', 'sessions' => function ($query) {
                $query->where('status', PosSession::STATUS_OPEN)->with('user');
            }])
            ->get();

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        // No se pasa nada de permisos a la vista: quien llega aquí ya pasó el
        // middleware 'permission:pos sessions manage' de la ruta.
        $view = view('livewire.sales.pos.pages.pos-terminal-lobby', [
            'terminals' => $terminals,
        ]);

        return $view->layout('layouts.pos');
    }
}
