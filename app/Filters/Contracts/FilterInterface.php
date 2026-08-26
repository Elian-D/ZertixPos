<?php

namespace App\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contrato de un filtro individual (REQ-0.3).
 *
 * Evolucionado desde apply(Builder $query) — que leía el valor de un
 * Request inyectado por constructor — a apply(Builder $query, mixed $value),
 * porque Livewire no tiene un Request por filtro: los valores viven en la
 * propiedad pública $filters del componente DataTable.
 *
 * $value llega garantizado no vacío (filled()) — no hace falta validar antes de aplicar.
 */
interface FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder;
}
