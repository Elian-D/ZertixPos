<?php

namespace App\Filters\PointOfSale;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class POSStateFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('state');

        return $value ? $query->where('provincia_id', $value) : $query;
    }
}
