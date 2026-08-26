<?php

namespace App\Filters\Categories;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class CategorySearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
              ->orWhere('id', 'like', "%{$value}%");
        });
    }
}
