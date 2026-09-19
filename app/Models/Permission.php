<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }

    /**
     * Filtro server-side real (REQ-2.7 punto 5) — la parte que de verdad cierra
     * el hueco de seguridad, nunca confía en que el checkbox llegó deshabilitado
     * desde el navegador. Recalcula qué nombres de permiso son asignables
     * ahora mismo (módulo activo o sin módulo) y devuelve la intersección con
     * lo que se intentó guardar. Un POST directo con el nombre de un permiso
     * de un módulo apagado se descarta igual, aunque alguien lo arme a mano.
     *
     * @param  string[]  $requested
     * @return string[]
     */
    public static function filterAssignable(array $requested): array
    {
        if (empty($requested)) {
            return [];
        }

        $assignable = static::query()
            ->whereIn('name', $requested)
            ->get(['name', 'module_key'])
            ->filter(fn (self $permission) => $permission->module_key === null || module_enabled($permission->module_key))
            ->pluck('name')
            ->all();

        return array_values(array_intersect($requested, $assignable));
    }
}
