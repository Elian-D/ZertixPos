<?php

namespace App\Models\Products;

use App\Traits\GuardsForceDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Unit extends Model
{
    use GuardsForceDelete, SoftDeletes;

    protected $table = 'units';

    protected $fillable = [
        'name',
        'abbreviation',
        'is_active',
    ];

    /* ===========================
     |  RELACIONES
     =========================== */

    /**
     * Relación: Una unidad tiene muchos productos
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

    /** Qué impide el borrado definitivo (v1.5.1 REQ-1.1). */
    public function deletionBlockers(): array
    {
        return [
            'producto|productos' => DB::table('products')->where('unit_id', $this->id)->count(),
        ];
    }
}
