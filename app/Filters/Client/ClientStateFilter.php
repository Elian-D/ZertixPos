<?php

namespace App\Filters\Client;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class ClientStateFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('provincia_id', $value);
    }
}
