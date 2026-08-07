<?php

namespace App\Filters\Client;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientStateFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('state');

        return $value ? $query->where('provincia_id', $value) : $query;
    }
}
