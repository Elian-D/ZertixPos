<?php

namespace App\Filters\Accounting\PaymentsFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class PaymentDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query
            ->when($from, function($q) use ($from) {
                // Si el input trae hora (T), startOfMinute lo respeta. 
                // Si solo trae fecha, startOfDay es el default.
                $date = Carbon::parse($from)->startOfMinute(); 
                return $q->where('payment_date', '>=', $date->toDateTimeString());
            })
            ->when($to, function($q) use ($to) {
                $date = Carbon::parse($to)->endOfMinute();
                return $q->where('payment_date', '<=', $date->toDateTimeString());
            });
    }
}