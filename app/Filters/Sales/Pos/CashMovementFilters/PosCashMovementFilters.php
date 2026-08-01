<?php

namespace App\Filters\Sales\Pos\CashMovementFilters;

use App\Filters\Base\QueryFilter;

class PosCashMovementFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'session_id' => CashMovementSessionFilter::class,
            'type'       => CashMovementTypeFilter::class,
            'user_id'    => CashMovementUserFilter::class,
            'from_date'  => CashMovementDateFilter::class,
        ];
    }
}