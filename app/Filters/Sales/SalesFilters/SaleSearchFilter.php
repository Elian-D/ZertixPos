<?php

namespace App\Filters\Sales\SalesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class SaleSearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('number', 'like', "%{$value}%")
              ->orWhere('notes', 'like', "%{$value}%")
              ->orWhereHas('client', function($subQ) use ($value) {
                  $subQ->where('name', 'like', "%{$value}%");
              });
        });
    }
}