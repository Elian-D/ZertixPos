<?php

namespace App\Filters\Client;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientTaxIdentifierFilter implements FilterInterface
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $value = $this->request->input('tax_type');

        return $value ? $query->where('tax_identifier_type', $value) : $query;
    }
}
