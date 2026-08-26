<?php

namespace App\Filters\Products;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ProductsCategoryFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('category_id', $value);
    }
}