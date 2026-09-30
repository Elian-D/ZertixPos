<?php

namespace App\Models\Sales\Returns;

use App\Models\Sales\SaleItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'sale_item_id',
        'quantity',
        'unit_subtotal',
        'unit_tax',
        'restock',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_subtotal' => 'decimal:4',
        'unit_tax' => 'decimal:4',
        'restock' => 'boolean',
    ];

    public function getSubtotalAttribute(): float
    {
        return round((float) $this->unit_subtotal * (float) $this->quantity, 2);
    }

    public function getTaxAttribute(): float
    {
        return round((float) $this->unit_tax * (float) $this->quantity, 2);
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->tax;
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
