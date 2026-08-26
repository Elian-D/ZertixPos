<?php

namespace App\Filters\Inventory\InventoryStockFilters;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class InventoryStockStatusFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return match ($value) {
            // Stock por debajo del mínimo (pero mayor a 0)
            'low' => $query->whereColumn('quantity', '<=', 'min_stock')
                           ->where('quantity', '>', 0),
            
            // Stock en cero o negativo
            'out' => $query->where('quantity', '<=', 0),
            
            // Stock saludable
            'ok' => $query->whereColumn('quantity', '>', 'min_stock'),
            
            default => $query,
        };
    }
}