<?php

namespace App\Models\Inventory;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Línea de una merma: producto, cantidad, motivo y su valor perdido al costo congelado. */
class InventoryWasteItem extends Model
{
    const REASON_DAMAGED = 'damaged';
    const REASON_EXPIRED = 'expired';
    const REASON_THEFT = 'theft';
    const REASON_INTERNAL_USE = 'internal_use';
    const REASON_OTHER = 'other';
    // Solo los crea el sistema, no se eligen a mano: una devolución dañada (REQ-2.3) y lo
    // que no llegó de una transferencia (REQ-2.4).
    const REASON_DAMAGED_RETURN = 'damaged_return';
    const REASON_TRANSIT_LOSS = 'transit_loss';

    protected $fillable = [
        'inventory_waste_id', 'product_id', 'quantity', 'reason', 'notes', 'unit_cost', 'total_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_value' => 'decimal:2',
    ];

    /** @param  bool  $manual  true = solo los que se eligen en el formulario. */
    public static function getReasons(bool $manual = false): array
    {
        $reasons = [
            self::REASON_DAMAGED => 'Dañado',
            self::REASON_EXPIRED => 'Vencido',
            self::REASON_THEFT => 'Robo o extravío',
            self::REASON_INTERNAL_USE => 'Consumo interno',
            self::REASON_OTHER => 'Otro',
            self::REASON_DAMAGED_RETURN => 'Devolución dañada',
            self::REASON_TRANSIT_LOSS => 'Pérdida en tránsito',
        ];

        return $manual
            ? array_diff_key($reasons, [self::REASON_DAMAGED_RETURN => true, self::REASON_TRANSIT_LOSS => true])
            : $reasons;
    }

    public function getReasonLabelAttribute(): string
    {
        return self::getReasons()[$this->reason] ?? $this->reason;
    }

    public function waste(): BelongsTo
    {
        return $this->belongsTo(InventoryWaste::class, 'inventory_waste_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
