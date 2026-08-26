<?php

namespace App\Filters\Accounting\ReceivablesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Carbon\Carbon;

class ReceivableOverdueFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        $today = Carbon::now()->format('Y-m-d');

        return $value === 'yes' 
            ? $query->where('due_date', '<', $today)->where('status', '!=', 'paid')
            : $query->where('due_date', '>=', $today);
    }
}