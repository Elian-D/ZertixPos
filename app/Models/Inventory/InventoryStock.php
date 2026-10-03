<?php

namespace App\Models\Inventory;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'min_stock',
        'max_stock', // v1.5.0 REQ-1.3: sobre stock, nullable
    ];

    /**
     * Relación con el almacén
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relación con el producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}