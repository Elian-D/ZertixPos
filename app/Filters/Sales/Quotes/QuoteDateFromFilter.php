<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class QuoteDateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereDate('created_at', '>=', $value);
    }
}
