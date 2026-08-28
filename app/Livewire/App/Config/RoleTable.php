<?php

namespace App\Livewire\App\Config;

use App\Livewire\Base\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

/**
 * Role (Spatie) no tiene SoftDeletes — destroy() ya era un hard-delete real,
 * sin papelera. Se queda como ruta real (modal de confirmación con <form>
 * POST), no se convierte a método Livewire.
 */
class RoleTable extends DataTable
{
    public array $filters = [
        'search' => '',
    ];

    protected function columns(): array
    {
        return [
            'id'         => ['label' => 'ID', 'default' => true],
            'name'       => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
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
        return $this->applyFilters(Role::query());
    }

    public function render()
    {
        $roles = $this->baseQuery()->orderBy('id')->paginate($this->perPage);

        return view('livewire.app.config.role-table', [
            'roles' => $roles,
        ]);
    }
}
