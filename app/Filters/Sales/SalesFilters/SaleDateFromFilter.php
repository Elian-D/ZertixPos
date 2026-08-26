<?php

namespace App\Filters\Sales\SalesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class SaleDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Ajustamos al inicio del minuto para incluir el segundo 0
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('sale_date', '>=', $date->toDateTimeString());
    }
}
