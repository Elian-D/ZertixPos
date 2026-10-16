<?php

namespace App\Models\Clients;

use App\Traits\GuardsForceDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BusinessType extends Model
{
    use GuardsForceDelete, HasFactory, SoftDeletes;

    protected $table = 'business_types';

    protected $fillable = [
        'nombre',
        'activo',
        'prefix',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /* ===========================
    |  COMPORTAMIENTO AUTOMÁTICO
     =========================== */
    protected static function booted()
    {
        static::creating(function ($businessType) {
            if (empty($businessType->prefix)) {
                $businessType->prefix = strtoupper(
                    substr(preg_replace('/[^A-Za-z]/', '', $businessType->nombre), 0, 3)
                );
            }
        });
    }

    /* ===========================
     |  SCOPES DEL CATÁLOGO
     =========================== */

    // Tipos de negocio habilitados
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Tipos de negocio deshabilitados
    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    // Scope flexible por estado (activo/inactivo)
    public function scopeFiltrarPorEstado($query, ?string $estado)
    {
        return match ($estado) {
            'activo' => $query->activos(),
            'inactivo' => $query->inactivos(),
            default => $query,
        };
    }

    /* ===========================
     |  COMPORTAMIENTO
     =========================== */

    /**
     * Alternar el estado activo/inactivo
     */
    public function toggleActivo(): void
    {
        $this->activo = ! $this->activo;
        $this->save();
    }

    /** Qué impide el borrado definitivo (v1.5.1 REQ-1.1). */
    public function deletionBlockers(): array
    {
        return [
            'punto de reparto|puntos de reparto' => DB::table('point_of_sales')->where('business_type_id', $this->id)->count(),
        ];
    }
}
