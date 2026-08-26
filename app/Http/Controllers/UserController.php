<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        // Trae todos los usuarios, ordenados por id
        $users = User::with('roles')   // Carga los roles en la misma consulta
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        // Límite de usuarios por plan (REQ-05.6) — la validación ya existía en
        // store(), pero solo se enteraba quien llegaba al final del formulario.
        // Acá se calcula para deshabilitar el botón "Crear Nuevo Usuario" con el
        // motivo visible, en vez de dejar que el usuario llene todo para recién
        // ahí rechazarlo.
        $plan = current_plan();
        $totalUsersCount = User::count(); // sin filtrar por búsqueda — es el conteo real contra el límite
        $usersLimit = $plan?->users_limit; // null = sin techo (PyME/Pro/Corporativo)
        $canCreateMoreUsers = ! $plan || $plan->canCreateMoreUsers();

        return view('users.index', compact('users', 'search', 'canCreateMoreUsers', 'usersLimit', 'totalUsersCount'));
    }

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
        // Validar ingreso del usuario
        $request->validate([
            'name' => 'required|string|unique:users,name',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Límite de usuarios por plan (REQ-05.6) — Emprendedor es de un solo
        // dueño/operador, PyME en adelante es multiusuario sin techo.
        $plan = current_plan();
        if ($plan && ! $plan->canCreateMoreUsers()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => "Tu plan actual ({$plan->name}) permite un máximo de {$plan->users_limit} usuario(s). Actualizá tu plan para agregar más.",
            ]);
        }

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Asignar rol por defecto a usuario
        $user->assignRole('Usuario Genérico');

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Validar los datos
        $request->validate([
            'name' => 'required|string|unique:users,name,'.$user->id,
            'email' => 'required|string|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],       // nueva contraseña opcional
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // Si el usuario ingresó nueva contraseña, actualizarla
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        // Actualizar el usuario
        $user->update($data);

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Usuario eliminado correctamente');
    }

    public function editRoles(User $user)
    {
        $roles = Role::all();

        // Obtener rol actuales del usuario
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('users.roles', compact('user', 'roles', 'userRoles'));
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->sync([$request->role_id]);

        return redirect()
            ->route('config.users.index')
            ->with('success', 'Rol actualizado correctamente.');
    }
}
