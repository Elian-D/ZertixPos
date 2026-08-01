<?php

namespace App\Filters\Sales\Pos\SessionFilters;

use App\Filters\Base\QueryFilter;

class PosSessionFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'terminal_id'       => PosSessionTerminalFilter::class,
            'user_id'           => PosSessionUserFilter::class,
            'opened_by_user_id' => PosSessionOpenedByFilter::class,
            'closed_by_user_id' => PosSessionClosedByFilter::class,
            'status'            => PosSessionStatusFilter::class,
            'difference_reason' => PosSessionDifferenceReasonFilter::class,
            'from_date'         => PosSessionDateFilter::class,
        ];
    }
}