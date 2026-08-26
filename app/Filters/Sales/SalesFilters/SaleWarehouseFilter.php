<?php

namespace App\Filters\Sales\SalesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class SaleWarehouseFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('warehouse_id', $value);
    }
}