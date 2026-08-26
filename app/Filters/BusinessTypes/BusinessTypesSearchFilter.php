<?php

namespace App\Filters\BusinessTypes;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class BusinessTypesSearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('nombre', 'like', "%{$value}%")
              ->orWhere('prefix', 'like', "%{$value}%");
        });
    }
}
