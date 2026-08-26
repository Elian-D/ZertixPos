<?php

namespace App\Filters\Sales\InvoiceFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class InvoiceDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Forzamos el fin del minuto para incluir registros hasta el segundo 59
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('created_at', '<=', $date->toDateTimeString());
    }
}
