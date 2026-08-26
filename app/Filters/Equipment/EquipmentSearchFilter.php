<?php

namespace App\Filters\Equipment;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class EquipmentSearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('code', 'like', "%{$value}%")
              ->orWhere('name', 'like', "%{$value}%")
              ->orWhere('serial_number', 'like', "%{$value}%")
              ->orWhere('model', 'like', "%{$value}%");
        });
    }
}
