<?php

namespace App\Services\Sales\Pos\PosSessionServices;

use App\Models\Sales\Pos\PosSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosSessionService
{

    /**
     * Abrir una nueva sesión de caja.
     */
    public function open(array $data): PosSession
    {
        return DB::transaction(function () use ($data) {
            $terminalId = $data['terminal_id'];
            $userId = Auth::id();

            // 1. Validar que la terminal no tenga una sesión abierta
            $activeTerminalSession = PosSession::where('terminal_id', $terminalId)
                ->open()
                ->exists();

            if ($activeTerminalSession) {
                throw ValidationException::withMessages([
                    'terminal_id' => 'Esta terminal ya tiene un turno activo.'
                ]);
            }

            // 2. Validar que el usuario no tenga OTRA sesión abierta en otra terminal
            $activeUserSession = PosSession::where('user_id', $userId)
                ->open()
                ->exists();

            if ($activeUserSession) {
                throw ValidationException::withMessages([
                    'user_id' => 'Ya tienes un turno abierto en otra terminal. Ciérralo antes de abrir uno nuevo.'
                ]);
            }

            // 3. Crear la sesión
            // 'user_id' se mantiene por compatibilidad con reportes/filtros existentes;
            // 'opened_by_user_id' es el campo canónico para saber quién abrió el turno.
            return PosSession::create([
                'terminal_id'       => $terminalId,
                'user_id'           => $userId,
                'opened_by_user_id' => $userId,
                'opened_at'         => now(),
                'opening_balance'   => $data['opening_balance'] ?? 0,
                'status'            => PosSession::STATUS_OPEN,
                'notes'             => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Calcula el monto que DEBERÍA haber en caja.
     */
    public function calculateExpected(PosSession $session): float
    {
        $opening = $session->opening_balance;

        // 1. Ventas en efectivo físico de esta sesión (incluye la porción en efectivo
        // de pagos divididos/mixtos). Ver el comentario en PosSession::getCashSalesAttribute()
        // para el porqué de sumar por sale_payments.tipo_pago_id en vez de payment_type.
        $cashSales = $session->cash_sales;

        // 2. Movimientos manuales (Entradas y Salidas de efectivo)
        $inflows = $session->cashMovements()->in()->sum('amount');
        $outflows = $session->cashMovements()->out()->sum('amount');

        // Formula: Inicial + Ventas Efectivo + Entradas Manuales - Salidas Manuales
        return (float) ($opening + $cashSales + $inflows) - $outflows;
    }

    /**
     * Cerrar la sesión con Arqueo Automático.
     */
    public function close(PosSession $session, array $data): bool
    {
        return DB::transaction(function () use ($session, $data) {
            if (!$session->isOpen()) {
                throw new \Exception("El turno ya se encuentra cerrado.");
            }

            // 1. Calculamos el esperado "la verdad del sistema"
            $expected = $this->calculateExpected($session);
            $real = $data['closing_balance'];
            $difference = $real - $expected;

            // 2. Persistimos la auditoría
            // closed_by_user_id puede ser distinto de user_id/opened_by_user_id si hubo
            // cambio de cajero durante el turno — ya no se exige que sea la misma persona.
            $session->update([
                'closed_at'         => now(),
                'closed_by_user_id' => Auth::id(),
                'expected_balance'  => $expected, // Grabamos lo que debió haber
                'closing_balance'   => $real,     // Lo que el cajero contó
                'difference'        => $difference, // El descuadre
                'difference_reason' => $data['difference_reason'] ?? null,
                'difference_notes'  => $data['difference_notes'] ?? null,
                'status'            => PosSession::STATUS_CLOSED,
                'notes'             => $data['notes'] ?? $session->notes,
            ]);

            return true;
        });
    }
    /**
     * Actualizar notas o datos menores sin cambiar el flujo de estado.
     */
    public function update(PosSession $session, array $data): bool
    {
        return $session->update($data);
    }
}

