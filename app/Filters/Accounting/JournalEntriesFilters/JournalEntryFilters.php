<?php

namespace App\Filters\Accounting\JournalEntriesFilters;

use App\Filters\Base\QueryFilter;

class JournalEntryFilters extends QueryFilter 
{
    protected function filters(): array 
    {
        return [
            'search'    => EntrySearchFilter::class,
            'status'    => EntryStatusFilter::class,
            'from_date' => EntryDateFromFilter::class,
            'to_date'   => EntryDateToFilter::class,
        ];
    }
}