<?php

namespace App\Filters\Inventory\InventoryStockFilters;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class InventoryStockCategoryFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereHas('product', function ($q) use ($value) {
            $q->where('category_id', $value);
        });
    }
}