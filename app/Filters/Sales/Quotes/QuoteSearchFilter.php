<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class QuoteSearchFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $value = $this->request->input('search');
        if (!$value) return $query;

        return $query->where(function($q) use ($value) {
            $q->where('id', 'like', "%$value%")
              ->orWhereHas('customer', function($sq) use ($value) {
                  $sq->where('name', 'like', "%$value%");
              });
        });
    }
}