<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'provinces';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function municipalities()
    {
        return $this->hasMany(Municipality::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }
}
