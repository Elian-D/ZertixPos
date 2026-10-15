<?php

namespace App\Models\Purchases;

use App\Models\Accounting\FinancialAccount;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Orden de compra (v1.5.0 REQ-5.1, OC): lo que se le pide al proveedor y cómo se va a pagar.
 *
 *  - Borrador: se edita; no mueve nada. No se borra (el número ya se emitió): se cancela.
 *  - Aprobada: ya no se edita y se puede recibir.
 *  - Recibida parcial / Recibida: salen solas de las recepciones; nunca se marcan a mano.
 *    Lo que el proveedor no va a mandar se cierra con una recepción que lo rechaza.
 *  - Cancelada: solo sin recepciones.
 *
 * "Enviada" no es un estado: es `sent_at`, y no bloquea recibir. La deuda nace al recibir,
 * no al aprobar. Ciclo de vida propio: sin SoftDeletes.
 */
class PurchaseOrder extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'OC';

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELED = 'canceled';

    /** Se puede recibir mercancía. */
    const RECEIVABLE_STATUSES = [self::STATUS_APPROVED, self::STATUS_PARTIAL];

    const CONDITION_CASH = 'cash';
    const CONDITION_CREDIT = 'credit';

    protected $fillable = [
        'vendor_id', 'warehouse_id', 'order_date', 'expected_date', 'payment_condition',
        'financial_account_id', 'total', 'notes', 'status', 'sent_at', 'sent_by',
        'created_by', 'approved_by', 'approved_at', 'canceled_by', 'canceled_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_PARTIAL => 'Recibida parcial',
            self::STATUS_RECEIVED => 'Recibida',
            self::STATUS_CANCELED => 'Cancelada',
        ];
    }

    public static function getConditions(): array
    {
        return [
            self::CONDITION_CASH => 'Contado',
            self::CONDITION_CREDIT => 'Crédito',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusVariantAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'info',
            self::STATUS_PARTIAL => 'primary',
            self::STATUS_RECEIVED => 'success',
            self::STATUS_CANCELED => 'error',
            default => 'warning',
        };
    }

    public function getConditionLabelAttribute(): string
    {
        return self::getConditions()[$this->payment_condition] ?? $this->payment_condition;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCash(): bool
    {
        return $this->payment_condition === self::CONDITION_CASH;
    }

    public function canReceive(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true);
    }

    /** Solo sin recepciones: una vez entra mercancía, la orden no se cancela. */
    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_APPROVED], true);
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['vendor:id,business_name,trade_name', 'warehouse:id,name'])
            ->withCount('items')
            ->withSum('items as quantity_total', 'quantity');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** De contado: la cuenta sugerida para pagar al recibir. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(PurchaseReception::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function canceler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }
}
