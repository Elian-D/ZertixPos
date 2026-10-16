<?php

namespace App\Models\Products;

use App\Traits\GuardsForceDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Category extends Model
{
    use GuardsForceDelete, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    /* ===========================
     |  RELACIONES
     =========================== */

    /**
     * Relación: Una categoría tiene muchos productos
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /* ===========================
     |  SCOPES DEL CATÁLOGO
     =========================== */

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('is_active', false);
    }

    /* ===========================
     |  COMPORTAMIENTO
     =========================== */

    /**
     * Alternar el estado activo/inactivo
     */
    public function toggleActivo(): void
    {
        // Corregido: Debe coincidir con el nombre de la columna en la DB
        $this->is_active = ! $this->is_active;
        $this->save();
    }

    /**
     * Boot del modelo para generar el slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    /** Qué impide el borrado definitivo (v1.5.1 REQ-1.1). */
    public function deletionBlockers(): array
    {
        return [
            'producto|productos' => DB::table('products')->where('category_id', $this->id)->count(),
        ];
    }
}
