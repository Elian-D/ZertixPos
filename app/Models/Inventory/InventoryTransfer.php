<?php

namespace App\Models\Inventory;

use App\Models\User;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Transferencia entre almacenes (v1.5.0 REQ-2.4, TRA).
 *
 *  - Borrador: se edita libremente; no mueve stock.
 *  - Enviada: cada línea sale del origen con 'transfer_out' (−). La mercancía queda en
 *    tránsito: ya no está en el origen y todavía no está en el destino. No se edita.
 *  - Recibida: entra al destino con 'transfer_in' (+) lo enviado y, si llegó menos, la
 *    diferencia sale enseguida como merma "Pérdida en tránsito" enlazada a la transferencia.
 *  - Cancelada: solo desde borrador (no se borra: el número ya se emitió).
 *
 * Ciclo de vida propio: sin SoftDeletes.
 */
class InventoryTransfer extends Model
{
    use HasDocumentNumber;

    const DOCUMENT_CODE = 'TRA';

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'from_warehouse_id', 'to_warehouse_id', 'notes', 'status',
        'created_by', 'sent_by', 'sent_at', 'received_by', 'received_at', 'canceled_by', 'canceled_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_SENT => 'En tránsito',
            self::STATUS_RECEIVED => 'Recibida',
            self::STATUS_CANCELED => 'Cancelada',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusVariantAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'info',
            self::STATUS_RECEIVED => 'success',
            self::STATUS_CANCELED => 'error',
            default => 'warning',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'creator:id,name'])
            ->withCount('items')
            ->withSum('items as quantity_sent_total', 'quantity_sent');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function canceler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    /** Merma de lo que se perdió en tránsito (si llegó menos de lo enviado). */
    public function waste(): MorphOne
    {
        return $this->morphOne(InventoryWaste::class, 'reference');
    }
}
