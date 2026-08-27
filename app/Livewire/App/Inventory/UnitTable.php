<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Products\Unit;
use Illuminate\Database\Eloquent\Builder;

class UnitTable extends DataTable
{
    public array $filters = [
        'search'    => '',
        'is_active' => '',
        'trashed'   => '',
    ];

    protected function columns(): array
    {
        return [
            'id'           => ['label' => 'ID', 'mobile' => true],
            'name'         => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'abbreviation' => ['label' => 'Abreviatura', 'default' => true],
            'is_active'    => ['label' => 'Estado', 'default' => true],
            'created_at'   => ['label' => 'Fecha Creación'],
            'updated_at'   => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('name', 'like', "%{$v}%")
                ->orWhere('id', 'like', "%{$v}%")),
            'is_active' => fn (Builder $q, $v) => $q->where('is_active', (bool) $v),
        ];
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default     => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? Unit::onlyTrashed()
            : Unit::query();

        return $this->applyFilters($query);
    }

    public function toggleActivo(int $id): void
    {
        abort_unless(auth()->user()->can('configure units'), 403);

        $unit = Unit::findOrFail($id);
        $unit->toggleActivo();

        $this->notify('success', 'Estado actualizado para "'.$unit->name.'".');
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('configure units'), 403);

        $unit = Unit::onlyTrashed()->findOrFail($id);
        $unit->restore();

        $this->notify('success', "Unidad de medida \"{$unit->name}\" restaurada correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('configure units'), 403);

        $unit = Unit::onlyTrashed()->findOrFail($id);
        $name = $unit->name;
        $unit->forceDelete();

        $this->notify('success', "Unidad de medida \"{$name}\" eliminada definitivamente.");
    }

    public function render()
    {
        $units = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.inventory.unit-table', [
            'units' => $units,
        ]);
    }
}
