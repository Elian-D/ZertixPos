<?php

namespace App\Filters\Sales\Pos\SessionFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;

class PosSessionOpenedByFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('opened_by_user_id');
        return $value ? $query->where('opened_by_user_id', $value) : $query;
    }
}
