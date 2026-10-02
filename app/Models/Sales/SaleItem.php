<?php

namespace App\Models\Sales;

use App\Models\Products\Product;
use App\Models\Sales\Returns\ReturnItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'discount_percentage',
        'subtotal',
        'tax_amount',
        'tax_breakdown',
    ];

    protected $casts = [
        'tax_breakdown' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    /**
     * Unidades de esta línea ya devueltas en devoluciones no anuladas.
     */
    public function returnedQuantity(): float
    {
        return (float) $this->returnItems()
            ->whereHas('saleReturn', fn ($q) => $q->active())
            ->sum('quantity');
    }

    public function returnableQuantity(): float
    {
        return max(0, (float) $this->quantity - $this->returnedQuantity());
    }
}
