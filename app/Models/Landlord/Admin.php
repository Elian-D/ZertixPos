<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    /**
     * Guard propio — separa los roles/permisos del landlord de los de
     * negocio (guard `web`, por tenant). Sin esto, Spatie asume el guard
     * por defecto y `assignRole()`/`can()` no matchean contra `admins`.
     */
    protected $guard_name = 'landlord';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
