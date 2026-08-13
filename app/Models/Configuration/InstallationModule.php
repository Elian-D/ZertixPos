<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Model;

class InstallationModule extends Model
{
    protected $fillable = ['module_key', 'is_enabled'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
