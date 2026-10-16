<?php

namespace App\Models\Inventory;

use App\Models\Sales\Returns\SaleReturn;
use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Merma (v1.5.0 REQ-2.2, MER): baja de mercancía perdida que ya se conoce (dañada,
 * vencida, robada, consumida), sin esperar a una toma física.
 *
 * Se aplica al guardar: cada línea saca stock con un movimiento 'waste' (−) y congela
 * su costo. Nunca se edita ni se borra; anularla devuelve el stock con un 'waste' (+)
 * por línea. Ciclo de vida propio: sin SoftDeletes.
 */
class InventoryWaste extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'MER';

    const STATUS_APPLIED = 'applied';
    const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'warehouse_id', 'waste_date', 'notes', 'status', 'total_value',
        'reference_type', 'reference_id',
        'created_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'waste_date' => 'date',
        'total_value' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_APPLIED => 'Aplicada',
            self::STATUS_VOIDED => 'Anulada',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusVariantAttribute(): string
    {
        return $this->status === self::STATUS_VOIDED ? 'error' : 'success';
    }

    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Documento que originó la merma, si no se registró a mano: etiqueta, enlace y por
     * qué no se anula sola. Requiere la relación `reference` cargada.
     *
     * @return array{label: string, url: string, note: string}|null
     */
    public function getOriginAttribute(): ?array
    {
        $ref = $this->reference;

        return match (true) {
            $ref instanceof SaleReturn => [
                'label' => "Devolución {$ref->number}",
                'url' => route('sales.returns.show', $ref),
                'note' => "Viene de la devolución {$ref->number}: se anula anulando la devolución.",
            ],
            $ref instanceof InventoryTransfer => [
                'label' => "Transferencia {$ref->number}",
                'url' => route('inventory.transfers.show', $ref),
                'note' => "Es lo que no llegó de la transferencia {$ref->number}: la pérdida ya ocurrió y no se anula.",
            ],
            default => null,
        };
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['warehouse:id,name', 'creator:id,name', 'items:id,inventory_waste_id,reason'])
            ->withCount('items');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Documento que la originó (devolución dañada, REQ-2.3). */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryWasteItem::class);
    }
}
