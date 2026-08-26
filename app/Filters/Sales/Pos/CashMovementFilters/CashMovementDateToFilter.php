<?php

namespace App\Filters\Sales\Pos\CashMovementFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class CashMovementDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Parseamos y aseguramos el final del minuto (59 segundos)
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('created_at', '<=', $date->toDateTimeString());
    }
}
