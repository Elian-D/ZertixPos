<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class NcfLogDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query
            ->when($from, function($q) use ($from) {
                // Estandarizado: Inicio del minuto
                $date = Carbon::parse($from)->startOfMinute(); 
                return $q->where('created_at', '>=', $date->toDateTimeString());
            })
            ->when($to, function($q) use ($to) {
                // Estandarizado: Fin del minuto
                $date = Carbon::parse($to)->endOfMinute();
                return $q->where('created_at', '<=', $date->toDateTimeString());
            });
    }
}