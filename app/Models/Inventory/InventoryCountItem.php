<?php

namespace App\Models\Inventory;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de una toma física. counted_quantity null = sin contar (no genera ajuste).
 * difference y unit_cost se congelan al aplicar; antes se calculan al vuelo.
 */
class InventoryCountItem extends Model
{
    protected $fillable = [
        'inventory_count_id', 'product_id', 'system_quantity', 'counted_quantity',
        'difference', 'unit_cost', 'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
        'difference' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function isCounted(): bool
    {
        return $this->counted_quantity !== null;
    }

    /** Diferencia congelada si ya se aplicó; si no, contada − existencia al iniciar. */
    public function getCurrentDifferenceAttribute(): ?float
    {
        if ($this->difference !== null) {
            return (float) $this->difference;
        }

        return $this->isCounted() ? (float) $this->counted_quantity - (float) $this->system_quantity : null;
    }

    /** Valor de la diferencia: al costo congelado o, en borrador, al costo actual del producto. */
    public function getDifferenceValueAttribute(): ?float
    {
        $diff = $this->current_difference;
        if ($diff === null) {
            return null;
        }

        $cost = $this->unit_cost !== null ? (float) $this->unit_cost : (float) ($this->product->cost ?? 0);

        return round($diff * $cost, 2);
    }

    // No se llama count(): chocaría con el count() de Eloquent.
    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class, 'inventory_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
