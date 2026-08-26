<?php

namespace App\Filters\PointOfSale;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class POSStateFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('provincia_id', $value);
    }
}
