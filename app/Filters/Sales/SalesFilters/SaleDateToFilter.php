<?php

namespace App\Filters\Sales\SalesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class SaleDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Ajustamos al final del minuto para incluir hasta el segundo 59
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('sale_date', '<=', $date->toDateTimeString());
    }
}
