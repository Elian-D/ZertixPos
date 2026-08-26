<?php

namespace App\Filters\Sales\InvoiceFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class InvoiceDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Forzamos el inicio del minuto para incluir registros desde el segundo 0
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('created_at', '>=', $date->toDateTimeString());
    }
}
