<?php

namespace App\Filters\Client;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ClientOverLimitFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $value === '1' 
            ? $query->whereColumn('balance', '>', 'credit_limit') 
            : $query;
    }
}