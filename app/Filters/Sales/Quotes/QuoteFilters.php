<?php

namespace App\Filters\Sales\Quotes;

use App\Filters\Base\QueryFilter;

class QuoteFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'      => QuoteSearchFilter::class,
            'customer_id' => QuoteCustomerFilter::class,
            'status'      => QuoteStatusFilter::class,
            'origin'      => QuoteOriginFilter::class,
            'user_id'     => QuoteUserFilter::class,
            'from_date'   => QuoteDateFromFilter::class,
            'to_date'     => QuoteDateToFilter::class,
        ];
    }
}