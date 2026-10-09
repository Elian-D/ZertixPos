<?php

namespace App\Models\Inventory;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Línea de una transferencia: lo enviado y, al recibir, lo que llegó. */
class InventoryTransferItem extends Model
{
    protected $fillable = ['inventory_transfer_id', 'product_id', 'quantity_sent', 'quantity_received', 'notes'];

    protected $casts = [
        'quantity_sent' => 'decimal:2',
        'quantity_received' => 'decimal:2',
    ];

    /** Lo que se perdió en tránsito (null si todavía no se recibe). */
    public function getShortageAttribute(): ?float
    {
        return $this->quantity_received === null
            ? null
            : max(0, round((float) $this->quantity_sent - (float) $this->quantity_received, 2));
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
