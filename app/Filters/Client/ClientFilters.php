<?php

namespace App\Filters\Client;

use App\Filters\Base\QueryFilter;

class ClientFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search' => ClientSearchFilter::class,
            'is_active' => ClientBusinessStatusFilter::class,
            'state' => ClientStateFilter::class,
            'type' => ClientTypeFilter::class,
            'tax_type' => ClientTaxIdentifierFilter::class,
            'from_date' => ClientDateFromFilter::class,
            'to_date' => ClientDateToFilter::class,
            'has_debt' => ClientHasDebtFilter::class,
            'over_limit' => ClientOverLimitFilter::class,
        ];
    }
}
