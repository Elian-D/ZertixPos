<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class QuoteSearchFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function($q) use ($value) {
            $q->where('id', 'like', "%$value%")
              ->orWhereHas('customer', function($sq) use ($value) {
                  $sq->where('name', 'like', "%$value%");
              });
        });
    }
}