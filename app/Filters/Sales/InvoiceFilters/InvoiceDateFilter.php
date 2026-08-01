<?php

namespace App\Filters\Sales\InvoiceFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class InvoiceDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query
            ->when($from, function($q) use ($from) {
                // Forzamos el inicio del minuto para incluir registros desde el segundo 0
                $date = Carbon::parse($from)->startOfMinute(); 
                return $q->where('created_at', '>=', $date->toDateTimeString());
            })
            ->when($to, function($q) use ($to) {
                // Forzamos el fin del minuto para incluir registros hasta el segundo 59
                $date = Carbon::parse($to)->endOfMinute();
                return $q->where('created_at', '<=', $date->toDateTimeString());
            });
    }
}