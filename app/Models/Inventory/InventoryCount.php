<?php

namespace App\Models\Inventory;

use App\Models\Products\Category;
use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Toma física (v1.5.0 REQ-2.1, TFS): contar lo que hay de verdad en un almacén y que
 * el sistema se ajuste a eso. Documento para firmar, no una compra.
 *
 * Cada línea guarda la "existencia al iniciar" (system_quantity) al crear la toma; la
 * diferencia se calcula contra ese número fijo y el ajuste se aplica sobre el stock
 * actual, así una venta durante el conteo no lo ensucia.
 *
 * Estados: borrador (se cuenta y se guarda a medias) → aplicada (movimientos 'count',
 * bloqueada) | cancelada (solo desde borrador). Ciclo de vida propio: sin SoftDeletes.
 */
class InventoryCount extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'TFS';

    const STATUS_DRAFT = 'draft';
    const STATUS_APPLIED = 'applied';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'warehouse_id', 'category_id', 'blind', 'notes', 'status', 'difference_value',
        'created_by', 'applied_by', 'applied_at', 'canceled_by', 'canceled_at',
    ];

    protected $casts = [
        'blind' => 'boolean',
        'difference_value' => 'decimal:2',
        'applied_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_APPLIED => 'Aplicada',
            self::STATUS_CANCELED => 'Cancelada',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /** Variante de x-ui.badge por estado. */
    public function getStatusVariantAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPLIED => 'success',
            self::STATUS_CANCELED => 'error',
            default => 'warning',
        };
    }

    /** "Todo el almacén" o el nombre de la categoría. */
    public function getScopeLabelAttribute(): string
    {
        return $this->category ? 'Categoría: '.$this->category->name : 'Todo el almacén';
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Regla del conteo ciego: mientras una toma ciega está en borrador, las diferencias
     * contra el sistema solo las ve quien puede aplicarla (el supervisor). Aplicada o
     * cancelada ya es un documento cerrado y la ve cualquiera con permiso de ver.
     */
    public function differencesVisibleTo(?User $user): bool
    {
        return ! ($this->blind && $this->isDraft()) || (bool) $user?->can('inventory_counts.apply');
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['warehouse:id,name', 'category:id,name', 'creator:id,name'])
            ->withCount([
                'items',
                'items as counted_items_count' => fn ($q) => $q->whereNotNull('counted_quantity'),
            ]);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function canceler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class);
    }
}
