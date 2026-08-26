<?php

namespace App\Filters\Sales\Pos\SessionFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class PosSessionDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('opened_at', '<=', Carbon::parse($value)->endOfMinute());
    }
}
