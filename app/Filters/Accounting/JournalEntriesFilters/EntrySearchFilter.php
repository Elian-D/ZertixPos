<?php

namespace App\Filters\Accounting\JournalEntriesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class EntrySearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('reference', 'like', "%{$value}%")
              ->orWhere('description', 'like', "%{$value}%")
              ->orWhere('id', 'like', "%{$value}%");
        });
    }
}