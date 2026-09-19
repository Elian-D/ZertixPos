<?php

namespace App\Livewire\App\Clients;

use App\Livewire\Base\DataTable;
use App\Models\Clients\BusinessType;
use Illuminate\Database\Eloquent\Builder;

class BusinessTypeTable extends DataTable
{
    public array $filters = [
        'search'  => '',
        'activo'  => '',
        'trashed' => '',
    ];

    protected function columns(): array
    {
        return [
            'id'         => ['label' => 'ID', 'default' => true, 'mobile' => true],
            'nombre'     => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'prefix'     => ['label' => 'Prefijo', 'default' => true],
            'activo'     => ['label' => 'Estado', 'default' => true],
            'created_at' => ['label' => 'Fecha Creación'],
            'updated_at' => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('nombre', 'like', "%{$v}%")
                ->orWhere('prefix', 'like', "%{$v}%")),
            'activo' => fn (Builder $q, $v) => $q->where('activo', filter_var($v, FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'activo' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default  => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? BusinessType::onlyTrashed()
            : BusinessType::query();

        return $this->applyFilters($query);
    }

    public function toggleActivo(int $id): void
    {
        abort_unless(auth()->user()->can('business_types.manage'), 403);

        $businessType = BusinessType::findOrFail($id);
        $businessType->toggleActivo();

        $this->notify('success', "Estado actualizado para \"{$businessType->nombre}\".");
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('business_types.manage'), 403);

        $businessType = BusinessType::onlyTrashed()->findOrFail($id);
        $businessType->restore();

        $this->notify('success', "Tipo de Negocio \"{$businessType->nombre}\" restaurado correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('business_types.manage'), 403);

        $businessType = BusinessType::onlyTrashed()->findOrFail($id);
        $nombre = $businessType->nombre;
        $businessType->forceDelete();

        $this->notify('success', "Tipo de Negocio \"{$nombre}\" eliminado definitivamente.");
    }

    public function render()
    {
        $businessTypes = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.clients.business-type-table', [
            'businessTypes' => $businessTypes,
        ]);
    }
}
