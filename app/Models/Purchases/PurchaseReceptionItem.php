<?php

namespace App\Models\Purchases;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Línea de una recepción: lo que entró, lo rechazado con su motivo y el costo de la factura. */
class PurchaseReceptionItem extends Model
{
    const REASON_DAMAGED = 'damaged';
    const REASON_EXPIRED = 'expired';
    const REASON_WRONG_PRODUCT = 'wrong_product';
    const REASON_NOT_AVAILABLE = 'not_available';
    const REASON_OTHER = 'other';

    protected $fillable = [
        'purchase_reception_id', 'purchase_order_item_id', 'product_id',
        'quantity_received', 'quantity_rejected', 'reject_reason', 'unit_cost', 'subtotal',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    public static function getRejectReasons(): array
    {
        return [
            self::REASON_DAMAGED => 'Dañado',
            self::REASON_EXPIRED => 'Vencido o próximo a vencer',
            self::REASON_WRONG_PRODUCT => 'Producto equivocado',
            self::REASON_NOT_AVAILABLE => 'El proveedor no lo va a enviar',
            self::REASON_OTHER => 'Otro',
        ];
    }

    public function getRejectReasonLabelAttribute(): ?string
    {
        return $this->reject_reason ? (self::getRejectReasons()[$this->reject_reason] ?? $this->reject_reason) : null;
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(PurchaseReception::class, 'purchase_reception_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
