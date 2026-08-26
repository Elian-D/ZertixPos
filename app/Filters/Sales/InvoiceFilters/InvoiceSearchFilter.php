<?php

namespace App\Filters\Sales\InvoiceFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class InvoiceSearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('invoice_number', 'like', "%{$value}%")
              ->orWhereHas('sale.client', function($subQ) use ($value) {
                  $subQ->where('name', 'like', "%{$value}%");
              });
        });
    }
}