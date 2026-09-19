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

    /**
     * Mismo formato que `User::getInitials()` — lo necesita el avatar del
     * menú de usuario en `components/sidebar/layout.blade.php`, reusado tal
     * cual para el Panel de Súper Admin (Fase 5, REQ-5).
     */
    public function getInitials(): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $this->name));
        $parts = explode(' ', $name);

        $initials = '';

        if (isset($parts[0])) {
            $initials .= strtoupper(substr($parts[0], 0, 1));
        }

        if (isset($parts[1])) {
            $initials .= strtoupper(substr($parts[1], 0, 1));
        }

        return $initials;
    }
}
