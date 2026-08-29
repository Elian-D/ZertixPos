<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Sales\Pos\PosCashMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Sales\Pos\PosSession;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Obtiene las iniciales del usuario (máximo dos letras) para el avatar.
     * Ej: "Elian David" -> "ED"
     * Ej: "Juan" -> "J"
     *
     * @return string
     */
    public function getInitials(): string
    {
        // Limpia el nombre y reemplaza múltiples espacios por uno solo
        $name = trim(preg_replace('/\s+/', ' ', $this->name));
        
        // Divide el nombre en palabras
        $parts = explode(' ', $name);
        
        $initials = '';
        
        // Toma la primera letra de la primera palabra
        if (isset($parts[0])) {
            $initials .= strtoupper(substr($parts[0], 0, 1));
        }
        
        // Si hay una segunda palabra, toma también su primera letra
        if (isset($parts[1])) {
            $initials .= strtoupper(substr($parts[1], 0, 1));
        }
        
        return $initials;
    }

    /**
     * Relación con las sesiones de punto de venta.
     */
    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    /**
     * Relación con los movimientos de caja realizados por el usuario.
     */
    public function posCashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(\App\Models\Sales\Quotes\Quote::class);
    }

    /**
     * Scope para filtrar usuarios que son cajeros o tienen acceso al POS.
     * Útil para el CatalogService.
     */
    public function scopeCashiers($query)
    {
        // Si usas permisos específicos:
        return $query->permission('pos_sessions.manage');
        
        // O si prefieres por rol:
        // return $query->role('cajero');
    }
}
