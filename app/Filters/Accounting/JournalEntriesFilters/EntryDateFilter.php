<?php

namespace App\Filters\Accounting\JournalEntriesFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Filters\Contracts\FilterInterface;
use Illuminate\Support\Carbon;

class EntryDateFilter implements FilterInterface 
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder 
    {
        $from = $this->request->input('from_date');
        $to = $this->request->input('to_date');

        return $query
            ->when($from, function($q) use ($from) {
                // startOfMinute asegura que incluimos desde el segundo :00
                $date = Carbon::parse($from)->startOfMinute(); 
                return $q->where('entry_date', '>=', $date->toDateTimeString());
            })
            ->when($to, function($q) use ($to) {
                // endOfMinute asegura que incluimos hasta el segundo :59
                $date = Carbon::parse($to)->endOfMinute();
                return $q->where('entry_date', '<=', $date->toDateTimeString());
            });
    }
}