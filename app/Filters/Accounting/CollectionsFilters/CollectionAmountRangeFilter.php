<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class CollectionAmountRangeFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $min = $this->request->input('min_amount');
        $max = $this->request->input('max_amount');

        return $query
            ->when($min, fn($q) => $q->where('amount', '>=', $min))
            ->when($max, fn($q) => $q->where('amount', '<=', $max));
    }
}
