<?php

namespace App\Filters\Inventory;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class MovementSearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->whereHas('product', fn($p) => $p->where('name', 'like', "%{$value}%"))
              ->orWhere('description', 'like', "%{$value}%");
        });
    }
}