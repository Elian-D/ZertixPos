<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class CollectionDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('payment_date', '<=', $date->toDateTimeString());
    }
}
