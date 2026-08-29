<?php

namespace App\Livewire\App\Config;

use App\Livewire\Base\DataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * REQ-2.7 (v1.3.0): `User` gana `SoftDeletes` — confirmado con un error real
 * (violación de FK al borrar un usuario con actividad, ver
 * docs/features/v1.3.0.md §2.7 punto 3), no la Categoría D/hard-delete que
 * este componente asumía antes. Papelera con una sola acción, a propósito
 * (mismo punto del doc): solo `restore()`, sin `forceDelete()` — no hay hoy
 * un requisito de purgar un usuario de forma permanente.
 */
class UserTable extends DataTable
{
    public array $filters = [
        'search' => '',
        // 'trashed' resuelve la papelera en baseQuery(), no en filterMap() —
        // mismo criterio que ClientTable (docs/analisis/politica-soft-deletes.md §6).
        'trashed' => '',
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

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? User::onlyTrashed()
            : User::query();

        return $this->applyFilters($query->with('roles'));
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('users.edit'), 403);

        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        $this->notify('success', "Usuario \"{$user->name}\" restaurado correctamente.");
    }

    public function render()
    {
        $users = $this->baseQuery()->orderBy('id')->paginate($this->perPage);

        // Límite de usuarios por plan (REQ-05.6) — se calcula acá para deshabilitar
        // el botón "Crear Nuevo Usuario" con el motivo visible en vez de dejar que
        // el usuario llene todo el formulario para recién ahí rechazarlo.
        $plan = current_plan();

        // REQ-2.7 punto 1 — calculado una sola vez acá (no por fila, evita N+1):
        // el único usuario activo con el rol protegido no puede eliminarse. `null`
        // si hay 2+ (o 0) — nadie queda protegido por esta regla en ese caso.
        $protectedUserId = User::role('admin')->count() === 1
            ? User::role('admin')->first()?->id
            : null;

        return view('livewire.app.config.user-table', [
            'users'              => $users,
            'canCreateMoreUsers' => ! $plan || $plan->canCreateMoreUsers(),
            'usersLimit'         => $plan?->users_limit,
            'totalUsersCount'    => User::count(),
            'protectedUserId'    => $protectedUserId,
        ]);
    }
}
