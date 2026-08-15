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
            'from_date'    => CollectionDateFilter::class, // Maneja to_date internamente
            'min_amount'   => CollectionAmountRangeFilter::class, // Maneja max_amount internamente
        ];
    }
}
