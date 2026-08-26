<?php

namespace App\Filters\Sales\InvoiceFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class InvoiceStatusFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('status', $value);
    }
}