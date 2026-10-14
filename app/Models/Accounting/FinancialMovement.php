<?php

namespace App\Models\Accounting;

use App\Models\Sales\SalePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Kardex de Cajas y Bancos (v1.5.0 REQ-3.1). Bitácora inmutable: nunca se edita ni se
 * borra; un error se corrige con su reverso (ORIGIN_REVERSAL), que deja los dos
 * movimientos enlazados (reverses_id / reversed_by_id).
 *
 * El monto es siempre positivo; la dirección dice si entra o sale. balance_after es el
 * saldo de la cuenta justo después de este movimiento, en el orden en que se registró.
 */
class FinancialMovement extends Model
{
    const DIRECTION_IN = 'in';
    const DIRECTION_OUT = 'out';

    const ORIGIN_OPENING = 'opening';
    const ORIGIN_DEPOSIT = 'deposit';
    const ORIGIN_WITHDRAWAL = 'withdrawal';
    const ORIGIN_TRANSFER = 'transfer';
    const ORIGIN_RECONCILIATION = 'reconciliation';
    const ORIGIN_CUSTOMER_PAYMENT = 'customer_payment'; // REQ-3.6: venta o cobro que no fue en efectivo
    const ORIGIN_SUPPLIER_PAYMENT = 'supplier_payment'; // Fase 4
    const ORIGIN_EXPENSE_PAYMENT = 'expense_payment';   // Fase 4
    const ORIGIN_REVERSAL = 'reversal';

    /** Los únicos que se anulan o corrigen desde Tesorería; los de un documento, desde ese documento. */
    const MANUAL_ORIGINS = [self::ORIGIN_DEPOSIT, self::ORIGIN_WITHDRAWAL, self::ORIGIN_TRANSFER];

    protected $fillable = [
        'financial_account_id', 'direction', 'amount', 'balance_after', 'origin', 'movement_date',
        'reference_type', 'reference_id', 'counterpart_id', 'reverses_id', 'reversed_by_id',
        'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'movement_date' => 'date',
    ];

    /** @param  bool  $available  sin los orígenes de la Fase 4, que todavía no existen */
    public static function getOrigins(bool $available = false): array
    {
        $origins = [
            self::ORIGIN_OPENING => 'Saldo inicial',
            self::ORIGIN_DEPOSIT => 'Depósito',
            self::ORIGIN_WITHDRAWAL => 'Retiro',
            self::ORIGIN_TRANSFER => 'Transferencia',
            self::ORIGIN_RECONCILIATION => 'Conciliación',
            self::ORIGIN_CUSTOMER_PAYMENT => 'Venta / cobro',
            self::ORIGIN_SUPPLIER_PAYMENT => 'Pago a proveedor',
            self::ORIGIN_EXPENSE_PAYMENT => 'Pago de gasto',
            self::ORIGIN_REVERSAL => 'Anulación',
        ];

        return $available
            ? array_diff_key($origins, array_flip([self::ORIGIN_SUPPLIER_PAYMENT, self::ORIGIN_EXPENSE_PAYMENT]))
            : $origins;
    }

    public function getOriginLabelAttribute(): string
    {
        return self::getOrigins()[$this->origin] ?? $this->origin;
    }

    public function getOriginVariantAttribute(): string
    {
        return match ($this->origin) {
            self::ORIGIN_OPENING => 'info',
            self::ORIGIN_DEPOSIT, self::ORIGIN_CUSTOMER_PAYMENT => 'success',
            self::ORIGIN_WITHDRAWAL, self::ORIGIN_SUPPLIER_PAYMENT, self::ORIGIN_EXPENSE_PAYMENT => 'warning',
            self::ORIGIN_TRANSFER => 'primary',
            self::ORIGIN_REVERSAL => 'error',
            default => 'slate',
        };
    }

    public function isIn(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    /** Monto con signo: + entra, − sale. */
    public function getSignedAmountAttribute(): float
    {
        return $this->isIn() ? (float) $this->amount : -(float) $this->amount;
    }

    /** Saldo de la cuenta justo antes de este movimiento (sin columna: después − monto). */
    public function getBalanceBeforeAttribute(): float
    {
        return round((float) $this->balance_after - $this->signed_amount, 2);
    }

    public function isReversed(): bool
    {
        return $this->reversed_by_id !== null;
    }

    /** Anulable o corregible desde Tesorería: manual y sin anular. */
    public function isEditable(): bool
    {
        return in_array($this->origin, self::MANUAL_ORIGINS, true) && ! $this->isReversed();
    }

    /**
     * Detalle legible para el kardex: la otra cuenta de una transferencia, qué anula un
     * reverso o el documento que lo originó. Requiere counterpart.account, reverses y
     * reference cargados.
     */
    public function getDetailAttribute(): ?string
    {
        if ($this->origin === self::ORIGIN_TRANSFER && $this->counterpart) {
            return ($this->isIn() ? 'Desde ' : 'Hacia ').($this->counterpart->account->name ?? '—');
        }

        if ($this->origin === self::ORIGIN_REVERSAL && $this->reverses) {
            return 'Anula '.mb_strtolower($this->reverses->origin_label).' del '.$this->reverses->movement_date->format('d/m/Y');
        }

        return $this->document['label'] ?? null;
    }

    /**
     * Documento de origen con su enlace: la venta o el cobro (REQ-3.6), la conciliación,
     * o el movimiento que corrigió. Requiere `reference` cargada.
     *
     * @return array{label: string, url: ?string}|null
     */
    public function getDocumentAttribute(): ?array
    {
        $ref = $this->reference;

        return match (true) {
            $ref instanceof SalePayment => [
                'label' => 'Venta '.($ref->sale->number ?? ''),
                'url' => $ref->sale ? route('sales.show', $ref->sale) : null,
            ],
            $ref instanceof ClientCollection => [
                'label' => 'Cobro '.$ref->receipt_number,
                'url' => route('finance.collections.show', $ref),
            ],
            $ref instanceof FinancialReconciliation => [
                'label' => 'Conciliación del '.$ref->reconciled_at->format('d/m/Y'),
                'url' => null,
            ],
            $ref instanceof self => [
                'label' => 'Corrige '.mb_strtolower($ref->origin_label).' de '.number_format((float) $ref->amount, 2),
                'url' => null,
            ],
            default => null,
        };
    }

    /** Lo que necesitan `detail`, `document` y el modal de detalle, sin N+1. */
    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'account:id,name,type,is_active,current_balance', 'creator:id,name', 'counterpart.account:id,name',
            'reverses', 'reversedBy:id,movement_date,created_at',
            'reference' => fn (MorphTo $morph) => $morph->morphWith([SalePayment::class => ['sale:id,number']]),
        ]);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function counterpart(): BelongsTo
    {
        return $this->belongsTo(self::class, 'counterpart_id');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    /** Documento que lo originó: venta, cobro, conciliación o el movimiento que corrige. */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
