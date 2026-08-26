<?php

namespace App\Filters\Warehouses;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class WarehousesActiveFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('is_active', (bool) $value);
    }
}