<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Conciliación de una cuenta (v1.5.0 REQ-3.3): el punto en que se le dice al sistema
 * cuánto hay de verdad (efectivo contado o saldo del banco). Si hay diferencia, la
 * corrige un movimiento 'reconciliation' que la referencia.
 *
 * Se guarda aunque no haya diferencia: "conciliada el 12/10, cuadró" también es historial.
 */
class FinancialReconciliation extends Model
{
    const REASON_BANK_FEE = 'bank_fee';
    const REASON_UNRECORDED_INCOME = 'unrecorded_income';
    const REASON_SHORTAGE = 'shortage';
    const REASON_RECORDING_ERROR = 'recording_error';
    const REASON_OTHER = 'other';

    protected $fillable = [
        'financial_account_id', 'reconciled_at', 'system_balance', 'real_balance',
        'difference', 'reason', 'notes', 'created_by',
    ];

    protected $casts = [
        'reconciled_at' => 'date',
        'system_balance' => 'decimal:2',
        'real_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public static function getReasons(): array
    {
        return [
            self::REASON_BANK_FEE => 'Comisión o cargo bancario',
            self::REASON_UNRECORDED_INCOME => 'Ingreso no registrado',
            self::REASON_SHORTAGE => 'Faltante',
            self::REASON_RECORDING_ERROR => 'Error de registro',
            self::REASON_OTHER => 'Otro',
        ];
    }

    public function getReasonLabelAttribute(): ?string
    {
        return $this->reason ? (self::getReasons()[$this->reason] ?? $this->reason) : null;
    }

    public function hasDifference(): bool
    {
        return round((float) $this->difference, 2) != 0;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movement(): MorphOne
    {
        return $this->morphOne(FinancialMovement::class, 'reference');
    }
}
