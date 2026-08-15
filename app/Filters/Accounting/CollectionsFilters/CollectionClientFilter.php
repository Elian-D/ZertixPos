<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class CollectionClientFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('client_id');
        return $value ? $query->where('client_id', $value) : $query;
    }
}
