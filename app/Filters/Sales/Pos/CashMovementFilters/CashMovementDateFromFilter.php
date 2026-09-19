<?php

namespace App\Filters\Sales\Pos\CashMovementFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class CashMovementDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Parseamos y aseguramos el inicio del minuto (00 segundos)
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('created_at', '>=', $date->toDateTimeString());
    }
}
