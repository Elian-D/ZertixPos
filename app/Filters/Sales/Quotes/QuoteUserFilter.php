<?php

namespace App\Filters\Sales\Quotes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class QuoteUserFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $value = $this->request->input('user_id');
        return $value ? $query->where('user_id', $value) : $query;
    }
}