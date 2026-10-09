<?php

namespace App\Models\Sales\Returns;

use App\Models\Accounting\DocumentType;
use App\Models\Inventory\InventoryWaste;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\Sales\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Devolución de una venta (v1.4.0 Fase 2). `Return` es palabra reservada de PHP,
 * por eso el modelo se llama SaleReturn sobre la tabla `returns`.
 */
class SaleReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'document_type_id',
        'number',
        'sale_id',
        'user_id',
        'reason',
        'refund_method',
        'refund_value',
        'cash_amount',
        'exchange_sale_id',
        'notes',
        'status',
        'voided_by',
        'voided_at',
    ];

    protected $casts = [
        'refund_value' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    const STATUS_COMPLETED = 'completed';

    const STATUS_VOIDED = 'voided';

    const METHOD_CASH = 'cash';

    const METHOD_EXCHANGE = 'exchange';

    const METHOD_RECEIVABLE = 'receivable';

    public static function getReasons(): array
    {
        return [
            'defective' => 'Producto defectuoso',
            'dispatch_error' => 'Error de despacho',
            'wrong_product' => 'Producto equivocado',
            'changed_mind' => 'Cliente cambió de opinión',
            'other' => 'Otro',
        ];
    }

    public static function getRefundMethods(): array
    {
        return [
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_EXCHANGE => 'Cambio de producto',
            self::METHOD_RECEIVABLE => 'Reducción de deuda',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_VOIDED => 'Anulada',
        ];
    }

    public function getReasonLabelAttribute(): string
    {
        return self::getReasons()[$this->reason] ?? $this->reason;
    }

    public function getRefundMethodLabelAttribute(): string
    {
        return self::getRefundMethods()[$this->refund_method] ?? $this->refund_method;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'sale:id,number,client_id,payment_type',
            'sale.client:id,name',
            'user:id,name',
            'exchangeSale:id,number',
            'items.saleItem.product:id,name,type',
        ]);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function exchangeSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'exchange_sale_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    /** Merma de las unidades dañadas que no regresaron a inventario (v1.5.0 REQ-2.3). */
    public function waste(): MorphOne
    {
        return $this->morphOne(InventoryWaste::class, 'reference');
    }
}
