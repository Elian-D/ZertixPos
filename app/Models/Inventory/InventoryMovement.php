<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;

class InventoryMovement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id', 
        'to_warehouse_id', // Para transferencias
        'product_id', 
        'user_id', 
        'quantity', 
        'type', 
        'previous_stock', // Auditoría
        'current_stock',  // Auditoría
        'description', 
        'reference_type', 
        'reference_id'
    ];

    const TYPE_INPUT = 'input';
    const TYPE_OUTPUT = 'output';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';
    // Devoluciones (v1.4.0 Fase 2): cantidad con signo, igual que TYPE_ADJUSTMENT —
    // +N entra la unidad devuelta, -N sale el reemplazo de un cambio o se revierte al anular.
    const TYPE_RETURN = 'return';

    public static function getTypes(): array
    {
        return [
            self::TYPE_INPUT => 'Entrada',
            self::TYPE_OUTPUT => 'Salida',
            self::TYPE_ADJUSTMENT => 'Ajuste',
            self::TYPE_TRANSFER => 'Transferencia',
            self::TYPE_RETURN => 'Devolución',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with(['warehouse', 'toWarehouse', 'user', 'product', 'reference']);
    }

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function toWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function reference(): MorphTo { return $this->morphTo(); }
}