<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class QuoteStatusFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('status', $value);
    }
}