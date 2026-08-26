<?php

namespace App\Filters\Sales\Pos\SessionFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class PosSessionDifferenceReasonFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('difference_reason', $value);
    }
}
