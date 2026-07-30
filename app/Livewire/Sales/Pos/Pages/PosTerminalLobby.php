<?php

namespace App\Livewire\Sales\Pos\Pages;

use Livewire\Component;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Pos\PosSession;
use Illuminate\Support\Facades\Auth;

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

        $terminal = PosTerminal::find($terminalId);
        if (!$terminal || !$terminal->is_active) {
            $this->addError('terminal', 'La terminal seleccionada no está disponible.');
            return;
        }

        // 1. Validar si tiene sesión activa de OTRO usuario
        $activeSession = $terminal->sessions()->where('status', PosSession::STATUS_OPEN)->first();

        if ($activeSession && $activeSession->user_id !== Auth::id()) {
            $this->addError('terminal', "Esta terminal está ocupada actualmente por el cajero: {$activeSession->user->name}.");
            return;
        }

        // 2. Si la terminal no tiene PIN de acceso configurado, saltamos la verificación
        if (!$terminal->requiresPinVerification()) {
            $this->markTerminalVerified($terminal->id);
            return $this->proceedToWorkspaceOrOpening($terminal);
        }

        // 3. Si ya está verificada en esta sesión (dentro de la ventana de 30 min de
        // CheckTerminalAccess) no volvemos a pedir el PIN. Bloquear (PosTerminalLockController)
        // sí borra esta marca explícitamente, así que tras un bloqueo real esto no aplica.
        if (session()->has("terminal_verified.{$terminal->id}")) {
            return $this->proceedToWorkspaceOrOpening($terminal);
        }

        // 4. Disparar de forma reactiva la apertura del modal de PIN vía eventos globales de Breeze
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

        if (!$terminal || !$terminal->verifyPin($this->pin)) {
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
        $activeSession = $terminal->sessions()->where('status', PosSession::STATUS_OPEN)->first();

        if ($activeSession && $activeSession->user_id === Auth::id()) {
            // Reanudación directa
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
        $this->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:500',
        ], [
            'opening_balance.required' => 'El fondo de apertura es obligatorio.',
            'opening_balance.numeric'  => 'Debe ingresar un valor numérico válido.',
        ]);

        $terminal = PosTerminal::find($this->selectedTerminalId);
        $exists = $terminal->sessions()->where('status', PosSession::STATUS_OPEN)->exists();
        
        if ($exists) {
            session()->flash('error', 'Esta terminal acaba de ser abierta por otra sesión.');
            return redirect()->route('pos.index');
        }

        PosSession::create([
            'terminal_id'     => $this->selectedTerminalId,
            'user_id'         => Auth::id(),
            'status'          => PosSession::STATUS_OPEN,
            'opened_at'       => now(),
            'opening_balance' => $this->opening_balance,
            'notes'           => $this->notes,
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
        $view = view('livewire.sales.pos.pages.pos-terminal-lobby', [
            'terminals' => $terminals
        ]);

        return $view->layout('layouts.pos');
    }
}