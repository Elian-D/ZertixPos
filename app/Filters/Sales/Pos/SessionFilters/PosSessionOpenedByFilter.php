<?php

namespace App\Filters\Sales\Pos\SessionFilters;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class PosSessionOpenedByFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('opened_by_user_id', $value);
    }
}
