<?php

namespace App\Models\Accounting;

use App\Models\Purchases\Vendor;
use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Cuenta por pagar (v1.5.0 REQ-4.2, CXP): una sola tabla para lo que se debe, sin importar
 * de dónde vino. Nace sola: de un gasto a crédito (§4.4) o, en la Fase 5, de recibir una
 * compra a crédito. No se crea a mano ni se edita.
 *
 * El estado sale del saldo: Pendiente (nada pagado), Parcial, Pagada; Anulada solo cuando
 * se anula su documento de origen (el gasto). Una CxP de una recepción no se anula: la
 * mercancía ya entró; solo se anulan sus pagos.
 */
class Payable extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'CXP';

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_VOIDED = 'voided';

    const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_PARTIAL];

    protected $fillable = [
        'vendor_id', 'source_type', 'source_id', 'concept', 'total_amount', 'balance',
        'supplier_invoice', 'issue_date', 'due_date', 'status',
        'created_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'voided_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PARTIAL => 'Parcial',
            self::STATUS_PAID => 'Pagada',
            self::STATUS_VOIDED => 'Anulada',
        ];
    }

    /** Origen para filtrar: la clase del documento. */
    public static function getOrigins(): array
    {
        return [
            Expense::class => 'Gasto',
            // PurchaseReception (Fase 5)
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isOverdue() ? 'Vencida' : (self::getStatuses()[$this->status] ?? $this->status);
    }

    public function getStatusVariantAttribute(): string
    {
        if ($this->isOverdue()) {
            return 'error';
        }

        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIAL => 'info',
            self::STATUS_VOIDED => 'slate',
            default => 'warning',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function getPaidAmountAttribute(): float
    {
        return round((float) $this->total_amount - (float) $this->balance, 2);
    }

    /** Origen con su enlace. Requiere `source` cargado. */
    public function getOriginAttribute(): ?array
    {
        $source = $this->source;

        return match (true) {
            $source instanceof Expense => [
                'label' => "Gasto {$source->number} · {$source->category_label}",
                'url' => route('finance.expenses.show', $source),
            ],
            default => null,
        };
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['vendor:id,business_name,trade_name', 'source']);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
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
