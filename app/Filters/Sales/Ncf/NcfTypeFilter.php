<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class NcfTypeFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('ncf_type_id', $value);
    }
}