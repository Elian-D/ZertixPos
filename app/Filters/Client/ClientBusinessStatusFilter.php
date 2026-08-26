<?php

namespace App\Filters\Client;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class ClientBusinessStatusFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
}
