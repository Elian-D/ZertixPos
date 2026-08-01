<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class QuoteDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                     ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to));
    }
}