<?php

namespace App\Filters\Accounting\ReceivablesFilters;

use App\Filters\Base\QueryFilter;

class ReceivableFilters extends QueryFilter 
{
    protected function filters(): array 
    {
        return [
            'search'    => ReceivableSearchFilter::class,
            'status'    => ReceivableStatusFilter::class,
            'client_id' => ReceivableClientFilter::class,
            'overdue'   => ReceivableOverdueFilter::class, // El de días vencidos
            'min_balance' => ReceivableBalanceMinFilter::class,
            'max_balance' => ReceivableBalanceMaxFilter::class,
        ];
    }
}