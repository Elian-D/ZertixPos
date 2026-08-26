<?php

namespace App\Filters\Accounting\ReceivablesFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ReceivableBalanceMaxFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('current_balance', '<=', $value);
    }
}
