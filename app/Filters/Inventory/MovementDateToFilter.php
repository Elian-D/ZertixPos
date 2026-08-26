<?php

namespace App\Filters\Inventory;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class MovementDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        $date = Carbon::parse($value)->endOfMinute();

        return $query->where('created_at', '<=', $date->format('Y-m-d H:i:s'));
    }
}
