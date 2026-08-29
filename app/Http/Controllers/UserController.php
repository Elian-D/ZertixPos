<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Rol base protegido — nunca se queda sin al menos un usuario activo con
     * este rol (REQ-2.7 punto 1). Mismo nombre sembrado en RoleSeeder::run().
     */
    private const PROTECTED_ROLE = 'admin';

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Config\UserTable.
     */
    public function index()
    {
        return view('users.index');
    }

    /**
     * REQ-2.7: selector de rol obligatorio + permisos extra opcionales, directo
     * en el formulario — ya no hay pantalla de asignación de rol aparte.
     */
    public function create()
    {
        $plan = current_plan();
        if ($plan && ! $plan->canCreateMoreUsers()) {
            return redirect()->route('config.users.index')->with('error', "Tu plan actual ({$plan->name}) permite un máximo de {$plan->users_limit} usuario(s). Actualizá tu plan para agregar más.");
        }

        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:users,name',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Límite de usuarios por plan (REQ-05.6) — Emprendedor es de un solo
        // dueño/operador, PyME en adelante es multiusuario sin techo.
        $plan = current_plan();
        if ($plan && ! $plan->canCreateMoreUsers()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => "Tu plan actual ({$plan->name}) permite un máximo de {$plan->users_limit} usuario(s). Actualizá tu plan para agregar más.",
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        // Filtro server-side (REQ-2.7 punto 5) — nunca confía en qué checkbox
        // llegó marcado desde el navegador.
        $extraPermissions = Permission::filterAssignable($request->permissions ?? []);
        if (! empty($extraPermissions)) {
            $user->givePermissionTo($extraPermissions);
        }

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        $userRoleId = $user->roles->first()?->id;

        // El bloque de rol/permisos extra solo se arma (y solo se persiste en
        // update(), ver abajo) si el actor tiene 'users.assign' — cambiar el
        // rol/permisos de un usuario existente es más sensible que editar su
        // nombre/email, se queda como gate propio (REQ-2.7). El resto (grupos,
        // qué otorga el rol elegido) lo resuelve App\Livewire\Shared\PermissionSelector.
        $canAssign = auth()->user()->can('users.assign');
        $userExtraPermissions = $canAssign
            ? $user->getDirectPermissions()->pluck('name')->toArray()
            : [];

        return view('users.edit', compact('user', 'userRoleId', 'canAssign', 'userExtraPermissions'));
    }

    public function update(Request $request, User $user)
    {
        $canAssign = auth()->user()->can('users.assign');

        $rules = [
            'name' => 'required|string|unique:users,name,'.$user->id,
            'email' => 'required|string|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($canAssign) {
            $rules['role_id'] = 'required|exists:roles,id';
            $rules['permissions'] = 'nullable|array';
            $rules['permissions.*'] = 'string|exists:permissions,name';
        }

        $request->validate($rules);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        // Solo se toca rol/permisos extra si el actor tiene el gate — un
        // 'role_id'/'permissions' colado en el POST por alguien sin
        // 'users.assign' se ignora, no solo se oculta en la UI.
        if ($canAssign) {
            $role = Role::findOrFail($request->role_id);
            $user->syncRoles([$role]);

            $extraPermissions = Permission::filterAssignable($request->permissions ?? []);
            $user->syncPermissions($extraPermissions);
        }

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        // REQ-2.7 punto 2: no borrar la propia cuenta desde la UI.
        if ($user->id === auth()->id()) {
            return redirect()->route('config.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        // REQ-2.7 punto 1: no dejar el rol protegido sin ningún usuario activo.
        if ($user->hasRole(self::PROTECTED_ROLE) && User::role(self::PROTECTED_ROLE)->count() <= 1) {
            return redirect()->route('config.users.index')
                ->with('error', 'No puedes eliminar el último usuario con el rol "'.self::PROTECTED_ROLE.'".');
        }

        // SoftDeletes (REQ-2.7 punto 3) — delete() ya es soft-delete real desde
        // que el trait se agregó al modelo, sin tocar nada acá.
        $user->delete();

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Usuario eliminado correctamente');
    }
}
