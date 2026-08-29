<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PermissionGroup extends Model
{
    protected $fillable = [
        'key',
        'label',
        'sort_order',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Grupos + permisos para las pantallas de Crear/Editar Rol y Crear/Editar
     * Usuario (REQ-2.7 puntos 5-6) — reemplaza `RoleController::editPermissions()`,
     * que ya no existe como pantalla aparte.
     *
     * Permisos de un módulo satélite/flexible apagado (`module_key` no nulo y
     * `module_enabled($module_key) === false`) se EXCLUYEN por completo, no se
     * muestran deshabilitados con un motivo (decisión 2026-08-29, corrige el
     * intento anterior) — mismo criterio que ya usa el sidebar con esos mismos
     * módulos: un link a una ruta gateada por `module:<key>` no aparece en el
     * menú si el módulo está apagado (y si se entra por URL directa, la ruta
     * misma devuelve 404 vía `EnsureModuleEnabled`). Un checkbox deshabilitado
     * con una nota ("requiere el módulo X") no aporta nada que el usuario
     * pueda accionar — todavía no puede marcarlo — y solo agrega ruido a una
     * pantalla que ya tiene 81 permisos repartidos en 8 grupos. Un grupo que
     * se queda sin ningún permiso asignable tampoco aparece (ej. si algún día
     * un grupo entero depende de un solo módulo apagado).
     *
     * `pos_cash_movements.create` se excluye a mano (REQ-2.2) — ruta dormida,
     * no debe aparecer ni poder asignarse mientras siga apagada.
     */
    public static function groupedForAssignment(): Collection
    {
        return static::query()
            ->orderBy('sort_order')
            ->with(['permissions' => fn ($query) => $query
                ->where('name', '!=', 'pos_cash_movements.create')
                ->orderBy('name')])
            ->get()
            ->each(function (self $group) {
                $group->setRelation('permissions', $group->permissions->filter(
                    fn (Permission $permission) => $permission->module_key === null || module_enabled($permission->module_key)
                )->values());
            })
            ->filter(fn (self $group) => $group->permissions->isNotEmpty())
            ->values();
    }
}
