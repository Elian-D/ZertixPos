<?php

namespace App\Models\Sales\Ncf;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcfSequence extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE    = 'active';
    const STATUS_EXHAUSTED = 'exhausted';
    const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'ncf_type_id', 'series', 'from', 'to', 'current', 
        'expiry_date', 'alert_threshold', 'status'
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    /**
     * Lógica de Agotamiento: ¿Quedan pocos números?
     */
    public function isLow(): bool
    {
        $remaining = $this->to - $this->current;
        return $remaining <= $this->alert_threshold;
    }

    /**
     * Diccionario de Etiquetas
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE    => 'Activa',
            self::STATUS_EXHAUSTED => 'Agotada',
            self::STATUS_EXPIRED   => 'Vencida',
        ];
    }

    /**
     * Estilos para Badges (Tailwind)
     */
    public static function getStatusStyles(): array
    {
        return [
            self::STATUS_ACTIVE    => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-500/10',
            self::STATUS_EXHAUSTED => 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-500/10',
            self::STATUS_EXPIRED   => 'bg-red-100 text-red-700 border-red-200 ring-red-500/10',
        ];
    }

    /**
     * Estado Dinámico: Valida vencimiento y disponibilidad.
     */
    public function getCalculatedStatusAttribute(): string
    {
        // 1. Prioridad: Vencimiento
        if ($this->expiry_date->isPast()) {
            return self::STATUS_EXPIRED;
        }

        // 2. Prioridad: Disponibilidad (Tu requerimiento)
        if (($this->to - $this->current) <= 0) {
            return self::STATUS_EXHAUSTED;
        }

        // 3. Por defecto lo que diga la base de datos (usualmente 'active')
        return $this->status;
    }

    /**
     * Accesor para obtener los estilos según el estado calculado
     */
    public function getStatusStylesAttribute(): string
    {
        $status = $this->calculated_status;
        return self::getStatusStyles()[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
    }

    /**
     * Accesor para obtener la etiqueta traducida
     */
    /** NCF completo para un número de esta secuencia (8 dígitos, 10 si es electrónico). */
    public function formatNumber(int $number): string
    {
        return $this->series.$this->type->code.str_pad($number, $this->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT);
    }

    /** Variante de x-ui.badge según el estado calculado (vistas show, patrón Infolist). */
    public function getStatusVariantAttribute(): string
    {
        return match ($this->calculated_status) {
            self::STATUS_ACTIVE => $this->isLow() ? 'warning' : 'success',
            self::STATUS_EXHAUSTED => 'warning',
            self::STATUS_EXPIRED => 'error',
            default => 'slate',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->calculated_status;
        return self::getStatuses()[$status] ?? $status;
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(NcfType::class, 'ncf_type_id');
    }
}