<?php

namespace App\Models\Accounting;

use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Pago de una cuenta por pagar (v1.5.0 REQ-4.3, EGR): sale de una cuenta de Cajas y
 * Bancos (movimiento 'supplier_payment' o 'expense_payment') y baja el saldo de la CxP.
 * Parcial permitido. Anularlo genera el reverso en la cuenta y devuelve el monto a la CxP.
 */
class PayablePayment extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'EGR';

    const STATUS_ACTIVE = 'active';
    const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'payable_id', 'financial_account_id', 'amount', 'payment_date', 'reference', 'notes',
        'status', 'created_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'voided_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Aplicado',
            self::STATUS_VOIDED => 'Anulado',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusVariantAttribute(): string
    {
        return $this->isVoided() ? 'error' : 'success';
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['payable:id,number,vendor_id,concept,source_type,source_id', 'payable.vendor:id,business_name,trade_name', 'account:id,name', 'creator:id,name']);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /** El movimiento que sacó el dinero de la cuenta. */
    public function movement(): MorphOne
    {
        return $this->morphOne(FinancialMovement::class, 'reference')->whereNot('origin', FinancialMovement::ORIGIN_REVERSAL);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
