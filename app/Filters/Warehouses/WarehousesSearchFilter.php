<?php

namespace App\Filters\Warehouses;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class WarehousesSearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('name', 'like', "%{$value}%");
    }
}
