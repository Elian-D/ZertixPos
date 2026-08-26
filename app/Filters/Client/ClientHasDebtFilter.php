<?php

namespace App\Filters\Client;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ClientHasDebtFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        if ($value === 'yes') {
            return $query->where('balance', '>', 0);
        }
        if ($value === 'no') {
            return $query->where('balance', '<=', 0);
        }
        return $query;
    }
}