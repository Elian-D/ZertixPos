<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class NcfLogDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Estandarizado: Fin del minuto
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('created_at', '<=', $date->toDateTimeString());
    }
}
