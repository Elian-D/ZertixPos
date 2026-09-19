<?php

namespace App\Filters\Accounting\JournalEntriesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class EntryDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // endOfMinute asegura que incluimos hasta el segundo :59
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('entry_date', '<=', $date->toDateTimeString());
    }
}
