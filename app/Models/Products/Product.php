<?php

// app/Models/Products/Product.php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'unit_id', 'name', 'slug', 'sku', 'description',
        'image_path', 'price', 'cost', 'is_active', 'is_stockable',
    ];

    /* ===========================
     |  ASESORES
     =========================== */

    public function getFormattedPriceAttribute(): string
    {
        return config('regional.currency_symbol').' '.number_format($this->price, 2);
    }

    /**
     * Precio con impuesto incluido — lo que el cliente realmente paga en caja.
     * Puramente de presentación (mismo criterio que grossPrice() en el POS
     * Workspace, REQ-5.9): `price` sigue siendo siempre el neto persistido,
     * ningún cálculo de venta/validación/asiento lee este accessor (Fase 5,
     * REQ-5.11).
     */
    public function getPriceWithTaxAttribute(): float
    {
        return round($this->price * (1 + $this->taxRate() / 100), 2);
    }

    /**
     * URL pública de la imagen — relativa (tercer argumento `false` de route()),
     * mismo criterio que el comentario original: evita imágenes rotas cuando el
     * puerto real del servidor difiere del configurado en APP_URL. Vía la ruta
     * de assets de stancl/tenancy (REQ-1.14, v1.3.0 Fase 1) en vez de un `/storage/`
     * a mano — bajo tenencia, el archivo físico vive en storage_path() sufijado
     * por tenant, no en el `public/storage` central.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('stancl.tenancy.asset', ['path' => $this->image_path], false) : null;
    }

    /* ===========================
     |  RELACIONES
     =========================== */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Para saber el stock en todos los almacenes
    public function stocks()
    {
        return $this->hasMany(\App\Models\Inventory\InventoryStock::class);
    }

    /* ===========================
     |  IMPUESTOS (Fase 5, REQ-5.1)
     =========================== */

    /**
     * Relación real con el pivote product_taxes — permite eager-load vía
     * scopeWithIndexRelations() para que taxes()/taxRate() no disparen una
     * query por fila en listados (ver ProductTable, N+1 detectado en la
     * migración a Livewire de REQ-0.8).
     */
    public function productTaxes()
    {
        return $this->hasMany(ProductTax::class);
    }

    /**
     * Claves de config('impuestos') asignadas a este producto.
     */
    public function taxes(): array
    {
        if ($this->relationLoaded('productTaxes')) {
            return $this->productTaxes->pluck('tax_key')->all();
        }

        return \Illuminate\Support\Facades\DB::table('product_taxes')
            ->where('product_id', $this->id)
            ->pluck('tax_key')
            ->all();
    }

    /**
     * Suma de tasas apiladas (ej. ITBIS 18% + ISC 10% = 28%).
     */
    public function taxRate(): float
    {
        return collect($this->taxes())->sum(fn ($key) => config("impuestos.{$key}.rate", 0));
    }

    /* ===========================
     |  SCOPES
     =========================== */
    /**
     * Tarea ERP Pattern: Centralizar Eager Loading
     */
    public function scopeWithIndexRelations(Builder $query): void
    {
        $query->with([
            'category:id,name',
            'unit:id,name,abbreviation',
            'productTaxes',
        ]); // Solo traemos lo necesario
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactivo(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeStockable(Builder $query): Builder
    {
        return $query->where('is_stockable', true);
    }

    // Para obtener la suma total de stock de este producto (el que borramos de la tabla)
    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }
}
