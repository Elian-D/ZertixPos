<?php

namespace App\Filters\Accounting\ReceivablesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ReceivableClientFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('client_id', $value);
    }
}