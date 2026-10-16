<?php

namespace App\Models\Purchases;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Línea de una orden de compra: lo pedido, a qué costo, y lo acumulado de sus recepciones. */
class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity', 'unit_cost', 'subtotal',
        'quantity_received', 'quantity_rejected',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
    ];

    /** Lo que falta por recibir: pedido − recibido − rechazado (lo rechazado cierra pendiente). */
    public function getPendingAttribute(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->quantity_received - (float) $this->quantity_rejected, 2));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
