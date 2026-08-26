<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class CollectionSearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('receipt_number', 'like', "%{$value}%")
              ->orWhere('reference', 'like', "%{$value}%")
              ->orWhere('note', 'like', "%{$value}%");
        });
    }
}
