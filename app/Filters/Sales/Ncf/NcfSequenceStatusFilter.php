<?php

namespace App\Filters\Sales\Ncf;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;
use App\Models\Sales\Ncf\NcfSequence;

class NcfSequenceStatusFilter implements FilterInterface 
{
    public function apply(Builder $query, mixed $value): Builder
    {
        $now = now()->format('Y-m-d');

        return match($value) {
            // Activos: Status DB es active Y no ha vencido Y quedan números
            NcfSequence::STATUS_ACTIVE => $query->where('status', NcfSequence::STATUS_ACTIVE)
                ->whereDate('expiry_date', '>', $now)
                ->whereRaw('(ncf_sequences.to - ncf_sequences.current) > 0'),

            // Agotados: La resta de to - current es 0 o menos
            NcfSequence::STATUS_EXHAUSTED => $query->whereRaw('(ncf_sequences.to - ncf_sequences.current) <= 0'),

            // Vencidos: La fecha de vencimiento ya pasó
            NcfSequence::STATUS_EXPIRED => $query->whereDate('expiry_date', '<=', $now),

            default => $query->where('status', $value),
        };
    }
}