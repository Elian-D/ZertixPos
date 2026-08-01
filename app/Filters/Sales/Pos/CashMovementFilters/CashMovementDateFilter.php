<?php

namespace App\Filters\Sales\Pos\CashMovementFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class CashMovementDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query
            ->when($from, function($q) use ($from) {
                // Parseamos y aseguramos el inicio del minuto (00 segundos)
                $date = Carbon::parse($from)->startOfMinute(); 
                return $q->where('created_at', '>=', $date->toDateTimeString());
            })
            ->when($to, function($q) use ($to) {
                // Parseamos y aseguramos el final del minuto (59 segundos)
                $date = Carbon::parse($to)->endOfMinute();
                return $q->where('created_at', '<=', $date->toDateTimeString());
            });
    }
}