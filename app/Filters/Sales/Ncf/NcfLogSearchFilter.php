<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class NcfLogSearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('full_ncf', 'like', "%{$value}%");
    }
}