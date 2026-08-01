<?php

namespace App\Filters\Sales\SalesFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class SaleDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query
            ->when($from, function($q) use ($from) {
                // Ajustamos al inicio del minuto para incluir el segundo 0
                $date = Carbon::parse($from)->startOfMinute(); 
                return $q->where('sale_date', '>=', $date->toDateTimeString());
            })
            ->when($to, function($q) use ($to) {
                // Ajustamos al final del minuto para incluir hasta el segundo 59
                $date = Carbon::parse($to)->endOfMinute();
                return $q->where('sale_date', '<=', $date->toDateTimeString());
            });
    }
}