<?php

namespace App\Filters\Accounting\CollectionsFilters;

use App\Filters\Base\QueryFilter;

class CollectionFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'       => CollectionSearchFilter::class,
            'client_id'    => CollectionClientFilter::class,
            'tipo_pago_id' => CollectionMethodFilter::class,
            'status'       => CollectionStatusFilter::class,
            'from_date'    => CollectionDateFromFilter::class,
            'to_date'      => CollectionDateToFilter::class,
            'min_amount'   => CollectionAmountMinFilter::class,
            'max_amount'   => CollectionAmountMaxFilter::class,
        ];
    }
}
