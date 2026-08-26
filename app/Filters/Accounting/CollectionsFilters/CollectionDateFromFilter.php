<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class CollectionDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Si el input trae hora (T), startOfMinute lo respeta.
        // Si solo trae fecha, startOfDay es el default.
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('payment_date', '>=', $date->toDateTimeString());
    }
}
