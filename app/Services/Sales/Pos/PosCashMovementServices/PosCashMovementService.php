<?php

namespace App\Services\Sales\Pos\PosCashMovementServices;

use App\Models\Sales\Pos\PosCashMovement;
use App\Models\Sales\Pos\PosSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class PosCashMovementService
{
    /**
     * Registra un movimiento de caja y dispara el evento para el asiento.
     */
    public function store(array $data): PosCashMovement
    {
        return DB::transaction(function () use ($data) {
            $session = PosSession::findOrFail($data['pos_session_id']);
            
            if (!$session->isOpen()) {
                throw new Exception("No se pueden registrar movimientos en una caja cerrada.");
            }

            $movement = PosCashMovement::create([
                'pos_session_id'        => $session->id,
                'user_id'               => Auth::id(),
                'accounting_account_id' => $data['accounting_account_id'], // <--- AGREGADO
                'type'                  => $data['type'],
                'amount'                => $data['amount'],
                'reason'                => $data['reason'],
                'reference'             => $data['reference'] ?? null,
                'metadata'              => $data['metadata'] ?? null,
            ]);

            return $movement;
        });
    }
}