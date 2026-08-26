<?php

namespace App\Filters\Inventory;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class MovementDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Carbon parsea el formato "2026-02-01T09:30" automáticamente
        $date = Carbon::parse($value)->startOfMinute();

        return $query->where('created_at', '>=', $date->format('Y-m-d H:i:s'));
    }
}
