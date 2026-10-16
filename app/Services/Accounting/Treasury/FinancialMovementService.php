<?php

namespace App\Services\Accounting\Treasury;

use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Único punto que escribe el kardex de Cajas y Bancos y el saldo de la cuenta
 * (v1.5.0 REQ-3.1). Bloquea la fila de la cuenta mientras calcula el saldo, igual que
 * DocumentType::issueNext(), para que dos movimientos simultáneos no se pisen.
 *
 * Reglas (REQ-3.2): una cuenta inactiva no recibe movimientos y ninguna acción manual la
 * deja en negativo. Lo automático de un documento ($fromDocument: la anulación de una
 * venta o de un cobro, y en la Fase 4 de un pago o un gasto) no se bloquea: ese dinero
 * nunca debió estar en la cuenta, y bloquearlo impediría anular el documento.
 *
 * Sin asiento contable (Decisión 4 de v1.5.0): si accounting.advanced se retoma, el
 * asiento de cada movimiento iría aquí, después de crear el movimiento.
 */
class FinancialMovementService
{
    /**
     * @param  array{reference_type?: ?string, reference_id?: ?int, counterpart_id?: ?int, reverses_id?: ?int, notes?: ?string}  $extra
     */
    public function register(
        FinancialAccount $account,
        string $direction,
        float $amount,
        string $origin,
        string $date,
        array $extra = [],
        bool $fromDocument = false,
    ): FinancialMovement {
        $amount = round($amount, 2);

        if ($amount < 0) {
            throw new DomainException('El monto de un movimiento no puede ser negativo.');
        }

        return DB::transaction(function () use ($account, $direction, $amount, $origin, $date, $extra, $fromDocument) {
            $account = FinancialAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if (! $account->is_active && ! $fromDocument) {
                throw new DomainException("La cuenta {$account->name} está inactiva: actívala para registrar movimientos.");
            }

            $signed = $direction === FinancialMovement::DIRECTION_IN ? $amount : -$amount;
            $balance = round((float) $account->current_balance + $signed, 2);

            if (! $fromDocument) {
                $this->assertNotNegative($account, $balance);
            }

            $movement = FinancialMovement::create([
                'financial_account_id' => $account->id,
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $balance,
                'origin' => $origin,
                'movement_date' => $date,
                'reference_type' => $extra['reference_type'] ?? null,
                'reference_id' => $extra['reference_id'] ?? null,
                'counterpart_id' => $extra['counterpart_id'] ?? null,
                'reverses_id' => $extra['reverses_id'] ?? null,
                'notes' => $extra['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $account->forceFill(['current_balance' => $balance])->save();

            return $movement;
        });
    }

    /**
     * Reverso de un movimiento: misma cuenta y monto, dirección contraria, enlazados.
     * Un reverso no se reversa. No valida quién puede anular qué: eso lo decide quien llama.
     */
    public function reverse(FinancialMovement $movement, string $reason, bool $fromDocument = false): FinancialMovement
    {
        return DB::transaction(function () use ($movement, $reason, $fromDocument) {
            $movement = FinancialMovement::whereKey($movement->id)->lockForUpdate()->firstOrFail();

            if ($movement->origin === FinancialMovement::ORIGIN_REVERSAL) {
                throw new DomainException('Una anulación no se puede anular.');
            }

            if ($movement->isReversed()) {
                throw new DomainException('Este movimiento ya está anulado.');
            }

            $reversal = $this->register(
                $movement->account,
                $movement->isIn() ? FinancialMovement::DIRECTION_OUT : FinancialMovement::DIRECTION_IN,
                (float) $movement->amount,
                FinancialMovement::ORIGIN_REVERSAL,
                now()->toDateString(),
                ['reverses_id' => $movement->id, 'notes' => $reason],
                $fromDocument,
            );

            $movement->forceFill(['reversed_by_id' => $reversal->id])->save();

            return $reversal;
        });
    }

    /** Valida el saldo final de una cuenta después de una operación de varios pasos (corregir). */
    public function assertAccountNotNegative(FinancialAccount $account): void
    {
        $account = $account->fresh();
        $this->assertNotNegative($account, round((float) $account->current_balance, 2));
    }

    private function assertNotNegative(FinancialAccount $account, float $balance): void
    {
        if ($balance < 0) {
            throw new DomainException(
                "Saldo insuficiente en {$account->name}: quedaría en −".number_format(abs($balance), 2)
                .'. Si la cuenta tiene más dinero del que dice el sistema, concíliala primero.'
            );
        }
    }
}
