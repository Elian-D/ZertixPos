<?php

namespace App\Filters\Sales\InvoiceFilters;

use App\Filters\Base\QueryFilter;

class InvoiceFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'      => InvoiceSearchFilter::class,
            'client_id'   => InvoiceClientFilter::class,
            'type'        => InvoiceTypeFilter::class,
            'status'      => InvoiceStatusFilter::class,
            'format_type' => InvoiceFormatFilter::class,
            'from_date'   => InvoiceDateFromFilter::class,
            'to_date'     => InvoiceDateToFilter::class,
        ];
    }
}