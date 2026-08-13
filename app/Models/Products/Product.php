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
     * URL pública de la imagen, relativa a la raíz (no absoluta vía asset()/APP_URL).
     * Evita imágenes rotas cuando el puerto real del servidor difiere del configurado en APP_URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? '/storage/'.$this->image_path : null;
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
