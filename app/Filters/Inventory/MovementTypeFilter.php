<?php

namespace App\Filters\Inventory;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class MovementTypeFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('type', $value);
    }
}