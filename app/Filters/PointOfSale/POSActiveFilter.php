<?php

namespace App\Filters\PointOfSale;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class POSActiveFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('active', $value);
    }
}