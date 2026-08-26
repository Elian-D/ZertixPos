<?php

namespace App\Filters\Units;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class UnitsSearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
              ->orWhere('id', 'like', "%{$value}%");
        });
    }
}
