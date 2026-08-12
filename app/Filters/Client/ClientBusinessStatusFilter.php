<?php

namespace App\Filters\Client;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientBusinessStatusFilter implements FilterInterface
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $query): Builder
    {
        if ($this->request->filled('is_active')) {
            $query->where('is_active', $this->request->boolean('is_active'));
        }

        return $query;
    }
}
