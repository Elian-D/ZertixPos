<?php

namespace App\Filters\Warehouses;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WarehousesSearchFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $search = $this->request->input('search');

        if (! $search) {
            return $query;
        }

        return $query->where('name', 'like', "%{$search}%");
    }
}
