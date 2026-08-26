<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class CollectionSearchFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('search');
        if (!$value) return $query;

        return $query->where(function($q) use ($value) {
            $q->where('receipt_number', 'like', "%{$value}%")
              ->orWhere('reference', 'like', "%{$value}%")
              ->orWhere('note', 'like', "%{$value}%");
        });
    }
}
