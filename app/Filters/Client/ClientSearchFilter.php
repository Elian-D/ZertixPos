<?php

namespace App\Filters\Client;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ClientSearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
              ->orWhere('tax_id', 'like', "%{$value}%")
              ->orWhere('email', 'like', "%{$value}%");
        });
    }
}
