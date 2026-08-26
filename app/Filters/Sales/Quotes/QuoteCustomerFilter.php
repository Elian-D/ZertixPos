<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class QuoteCustomerFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('customer_id', $value);
    }
}