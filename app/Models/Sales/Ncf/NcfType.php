<?php

namespace App\Models\Sales\Ncf;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NcfType extends Model
{
    protected $fillable = ['name', 'prefix', 'code', 'is_electronic', 'requires_rnc', 'is_active'];

    /**
     * Relación con las secuencias cargadas.
     */
    public function sequences(): HasMany
    {
        return $this->hasMany(NcfSequence::class);
    }

    /**
     * Relación con los logs de uso.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NcfLog::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /** REQ-7.3 — formato DGII combinado (ej. "B01"/"E31") para la columna Código del listado. */
    public function getFullCodeAttribute(): string
    {
        return "{$this->prefix}{$this->code}";
    }
}
