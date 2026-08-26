<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class NcfLogStatusFilter implements FilterInterface 
{
    // app/Filters/Sales/Ncf/NcfLogStatusFilter.php
    public function apply(Builder $query, mixed $value): Builder
    {
        if ($value) {
            return $query->where('ncf_logs.status', $value);
        }

        return $query;
    }
}