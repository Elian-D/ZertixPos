<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Models\Sales\Returns\SaleReturn;
use App\Models\Sales\Sale;

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

    // v1.5.0 REQ-1.1: cada documento con nombre deja su propio tipo en el kardex.
    const TYPE_INITIAL = 'initial';
    const TYPE_SALE = 'sale';
    const TYPE_SALE_VOID = 'sale_void';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_COUNT = 'count';
    const TYPE_WASTE = 'waste';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_TRANSFER_IN = 'transfer_in';
    // Devoluciones (v1.4.0 Fase 2): cantidad con signo —
    // +N entra la unidad devuelta, -N sale el reemplazo de un cambio o se revierte al anular.
    const TYPE_RETURN = 'return';
    // Ajuste manual (REQ-1.5): corrige un error que ningún documento puede anular. Solo
    // desde la vista "Nuevo ajuste" del kardex, con permiso propio, motivo y comentario.
    // Los tipos libres de antes (input/output/adjustment/transfer) ya no existen.
    const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

    /** Signo con el que register() aplica la cantidad: 0 = con signo tal cual llega. */
    const SIGN_IN = 1;
    const SIGN_OUT = -1;
    const SIGN_SIGNED = 0;

    /**
     * Fuente única por tipo: etiqueta, signo y color del badge (hex, porque
     * x-ui.badge solo trae 6 variantes y cada tipo necesita el suyo).
     */
    private const TYPE_MAP = [
        self::TYPE_INITIAL      => ['label' => 'Inventario inicial',     'sign' => self::SIGN_IN,     'hex' => '#4f46e5'],
        self::TYPE_SALE         => ['label' => 'Venta',                  'sign' => self::SIGN_OUT,    'hex' => '#0284c7'],
        self::TYPE_SALE_VOID    => ['label' => 'Anulación de venta',     'sign' => self::SIGN_IN,     'hex' => '#ea580c'],
        self::TYPE_PURCHASE     => ['label' => 'Compra',                 'sign' => self::SIGN_IN,     'hex' => '#16a34a'],
        self::TYPE_COUNT        => ['label' => 'Ajuste por toma física', 'sign' => self::SIGN_SIGNED, 'hex' => '#7c3aed'],
        self::TYPE_WASTE        => ['label' => 'Merma',                  'sign' => self::SIGN_SIGNED, 'hex' => '#dc2626'],
        self::TYPE_TRANSFER_OUT => ['label' => 'Transferencia enviada',  'sign' => self::SIGN_OUT,    'hex' => '#0891b2'],
        self::TYPE_TRANSFER_IN  => ['label' => 'Transferencia recibida', 'sign' => self::SIGN_IN,     'hex' => '#0d9488'],
        self::TYPE_RETURN       => ['label' => 'Devolución',             'sign' => self::SIGN_SIGNED, 'hex' => '#d97706'],
        self::TYPE_ADJUSTMENT_IN  => ['label' => 'Ajuste de entrada',    'sign' => self::SIGN_IN,     'hex' => '#65a30d'],
        self::TYPE_ADJUSTMENT_OUT => ['label' => 'Ajuste de salida',     'sign' => self::SIGN_OUT,    'hex' => '#be185d'],
    ];

    /** Motivos del ajuste manual (selector de la vista "Nuevo ajuste"). */
    public static function getAdjustmentReasons(): array
    {
        return [
            'sale_error' => 'Corrección de venta',
            'purchase_error' => 'Corrección de compra',
            'waste_error' => 'Corrección de merma',
            'count_error' => 'Corrección de toma física',
            'found' => 'Mercancía encontrada',
            'other' => 'Otro',
        ];
    }

    public static function getTypes(): array
    {
        return array_map(fn ($t) => $t['label'], self::TYPE_MAP);
    }

    public static function signFor(string $type): int
    {
        return self::TYPE_MAP[$type]['sign'] ?? self::SIGN_SIGNED;
    }

    public static function hexFor(string $type): string
    {
        return self::TYPE_MAP[$type]['hex'] ?? '#64748b';
    }

    /**
     * Origen legible del movimiento para la UI ("Venta VTA-000012", "Devolución DEV-000040"),
     * con enlace a su show si existe. Usa el número del documento solo si la relación
     * `reference` ya viene cargada (withIndexRelations la trae); si no, cae al id —
     * nunca dispara una consulta por fila.
     *
     * @return array{label: string, url: ?string}
     */
    public function getOriginAttribute(): array
    {
        $id = $this->reference_id;
        $number = $this->relationLoaded('reference') ? ($this->reference?->number ?? null) : null;
        $ref = $number ?: "#{$id}";

        if (in_array($this->type, [self::TYPE_ADJUSTMENT_IN, self::TYPE_ADJUSTMENT_OUT], true)) {
            return ['label' => 'Ajuste manual', 'url' => null];
        }

        return match ($this->reference_type) {
            null, '' => ['label' => 'Sin documento', 'url' => null],
            Sale::class => ['label' => "Venta {$ref}", 'url' => route('sales.show', $id)],
            SaleReturn::class => ['label' => "Devolución {$ref}", 'url' => route('sales.returns.show', $id)],
            Product::class => ['label' => 'Inventario inicial', 'url' => route('inventory.products.show', $id)],
            InventoryCount::class => ['label' => "Toma física {$ref}", 'url' => route('inventory.counts.show', $id)],
            InventoryWaste::class => ['label' => "Merma {$ref}", 'url' => route('inventory.wastes.show', $id)],
            InventoryTransfer::class => ['label' => "Transferencia {$ref}", 'url' => route('inventory.transfers.show', $id)],
            default => ['label' => class_basename($this->reference_type)." {$ref}", 'url' => null],
        };
    }

    /** Color del tipo para x-ui.badge :hex (kardex, show del producto, dashboard). */
    public function getTypeHexAttribute(): string
    {
        return self::hexFor($this->type);
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