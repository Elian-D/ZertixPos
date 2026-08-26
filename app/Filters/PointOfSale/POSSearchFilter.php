<?php

namespace App\Filters\PointOfSale;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class POSSearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
              ->orWhere('code', 'like', "%{$value}%")
              ->orWhere('contact_name', 'like', "%{$value}%");
        });
    }
}