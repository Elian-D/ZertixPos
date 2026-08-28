<?php

namespace App\Livewire\App\Config;

use App\Livewire\Base\DataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * User no tiene SoftDeletes (Categoría D, catálogo de bajo movimiento) —
 * destroy() ya era un hard-delete real vía UserController, sin papelera. Se
 * queda como ruta real (modal de confirmación con <form> POST), no se
 * convierte a método Livewire.
 */
class UserTable extends DataTable
{
    public array $filters = [
        'search' => '',
    ];

    protected function columns(): array
    {
        return [
            'avatar'     => ['label' => 'Avatar', 'default' => true],
            'name'       => ['label' => 'Nombre y Email', 'default' => true, 'mobile' => true],
            'roles'      => ['label' => 'Rol', 'default' => true, 'mobile' => true],
            'created_at' => ['label' => 'Creado', 'default' => true],
            'updated_at' => ['label' => 'Actualizado'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where('name', 'like', "%{$v}%"),
        ];
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(User::query()->with('roles'));
    }

    public function render()
    {
        $users = $this->baseQuery()->orderBy('id')->paginate($this->perPage);

        // Límite de usuarios por plan (REQ-05.6) — se calcula acá para deshabilitar
        // el botón "Crear Nuevo Usuario" con el motivo visible en vez de dejar que
        // el usuario llene todo el formulario para recién ahí rechazarlo.
        $plan = current_plan();

        return view('livewire.app.config.user-table', [
            'users'              => $users,
            'canCreateMoreUsers' => ! $plan || $plan->canCreateMoreUsers(),
            'usersLimit'         => $plan?->users_limit,
            'totalUsersCount'    => User::count(),
        ]);
    }
}
