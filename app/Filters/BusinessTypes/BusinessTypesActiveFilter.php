<?php

namespace App\Filters\BusinessTypes;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class BusinessTypesActiveFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('activo', (bool) $value);
    }
}
