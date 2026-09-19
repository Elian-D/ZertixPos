<?php

namespace App\Livewire\Shared;

use App\Models\PermissionGroup;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Selector de permisos agrupados (8 grupos, REQ-2.5), embebido en
 * roles/create+edit y users/create+edit (REQ-2.7 punto 6) dentro de un
 * `<form>` HTML plano — este componente no hace su propio submit, solo
 * renderiza `<select name="role_id">`/`<input name="permissions[]">` reales
 * para que el POST nativo del formulario que lo envuelve los recoja.
 *
 * La única razón de ser Livewire (no un partial Blade+Alpine, que fue el
 * primer intento) es la interactividad real: en Usuarios, cambiar el rol
 * elegido tiene que re-pintar en el momento qué permisos ya trae ese rol
 * (marcados y bloqueados, "no son extra") sin recargar la página — Alpine no
 * puede recalcular eso solo, necesita el set de permisos del rol nuevo desde
 * el servidor.
 */
class PermissionSelector extends Component
{
    /** Permisos ya marcados al montar (extra en Usuarios, todos en Roles). */
    public array $checkedNames = [];

    /** Rol elegido — null en Roles (no aplica), id de rol en Usuarios. */
    public ?int $roleId = null;

    /** Usuarios embebe el `<select>` de rol adentro de este componente (para
     *  que cambiarlo re-renderice reactivamente); Roles no lo necesita. */
    public bool $showRoleSelect = false;

    public function mount(array $checkedNames = [], ?int $roleId = null, bool $showRoleSelect = false): void
    {
        $this->checkedNames = $checkedNames;
        $this->roleId = $roleId;
        $this->showRoleSelect = $showRoleSelect;
    }

    private function includedNames(): array
    {
        if (! $this->roleId) {
            return [];
        }

        return Role::find($this->roleId)?->permissions->pluck('name')->all() ?? [];
    }

    public function render()
    {
        return view('livewire.shared.permission-selector', [
            'groupedPermissions' => PermissionGroup::groupedForAssignment(),
            'includedNames' => $this->includedNames(),
            'roles' => $this->showRoleSelect ? Role::orderBy('name')->get() : collect(),
        ]);
    }
}
