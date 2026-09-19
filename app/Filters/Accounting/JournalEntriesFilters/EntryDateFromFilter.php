<?php

namespace App\Filters\Accounting\JournalEntriesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class EntryDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // startOfMinute asegura que incluimos desde el segundo :00
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('entry_date', '>=', $date->toDateTimeString());
    }
}
