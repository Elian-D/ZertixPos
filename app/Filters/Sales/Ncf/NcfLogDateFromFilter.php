<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class NcfLogDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Estandarizado: Inicio del minuto
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('created_at', '>=', $date->toDateTimeString());
    }
}
