<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Usuarios listados en el show del rol (el resto, en el listado de usuarios). */
    private const USERS_LIMIT = 30;

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Config\RoleTable.
     */
    public function index()
    {
        return view('roles.index');
    }

    /**
     * Detalle del rol (v1.4.0 Fase 3, patrón Infolist — /filament-show): datos,
     * usuarios que lo tienen y permisos agrupados por módulo.
     */
    public function show(Role $role)
    {
        $role->load('permissions');
        $users = User::role($role->name);

        return view('roles.show', [
            'role' => $role,
            'groups' => PermissionGroup::groupPermissions($role->permissions),
            'users' => (clone $users)->orderBy('name')->limit(self::USERS_LIMIT)->get(['id', 'name', 'email']),
            'usersCount' => (clone $users)->count(),
            'usersLimit' => self::USERS_LIMIT,
        ]);
    }

    /**
     * REQ-2.7 punto 6: ya no hay pantalla de "asignar permisos" aparte — los
     * checkboxes de permisos (agrupados, con el filtro de módulo de REQ-2.7
     * punto 5 ya aplicado) viven directo acá.
     */
    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        // Filtro server-side (REQ-2.7 punto 5) — nunca confía en qué checkbox
        // llegó marcado desde el navegador.
        $role->syncPermissions(Permission::filterAssignable($request->permissions ?? []));

        return redirect()->route('config.roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function edit(Role $role)
    {
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions(Permission::filterAssignable($request->permissions ?? []));

        return redirect()->route('config.roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        // REQ-2.7 punto 4: un rol en uso no se borra — dejaría a sus usuarios
        // sin permisos de golpe, sin aviso (incidente de acceso). Incluye
        // usuarios en papelera: un usuario soft-deleted tuvo ese rol
        // históricamente y, si se restaura, se espera que el rol siga
        // existiendo. Sin usuarios (ni en papelera), el borrado es real y
        // directo — sin tab de Papelera para roles (config, no bitácora).
        if (User::withTrashed()->role($role)->exists()) {
            $count = User::withTrashed()->role($role)->count();

            return redirect()->route('config.roles.index')
                ->with('error', "No se puede eliminar: tiene {$count} usuario(s) asignado(s).");
        }

        $role->delete();

        return redirect()->route('config.roles.index')->with('success', 'Rol eliminado correctamente.');
    }
}
