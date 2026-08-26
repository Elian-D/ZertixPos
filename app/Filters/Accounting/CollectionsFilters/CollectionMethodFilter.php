<?php

namespace App\Filters\Accounting\CollectionsFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class CollectionMethodFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('tipo_pago_id');
        return $value ? $query->where('tipo_pago_id', $value) : $query;
    }
}
