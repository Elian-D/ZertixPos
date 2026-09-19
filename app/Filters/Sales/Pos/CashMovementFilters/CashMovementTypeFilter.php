<?php

namespace App\Filters\Sales\Pos\CashMovementFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class CashMovementTypeFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('type', $value);
    }
}