<?php

namespace App\Filters\Client;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ClientDateToFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereDate('created_at', '<=', $value);
    }
}
