<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Inventory\Warehouse;
use App\Services\Inventory\WarehouseService\WarehouseCatalogService;
use Illuminate\Database\Eloquent\Builder;

class WarehouseTable extends DataTable
{
    public array $filters = [
        'search'    => '',
        'type'      => '',
        'is_active' => '',
        'trashed'   => '',
    ];

    protected function columns(): array
    {
        return array_filter([
            'name'                  => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'types'                 => ['label' => 'Tipo', 'default' => true],
            'accounting_account_id' => module_enabled('accounting.advanced')
                ? ['label' => 'Cuenta Contable', 'default' => true]
                : null,
            'address'     => ['label' => 'Ubicación'],
            'description' => ['label' => 'Descripción'],
            'is_active'   => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_at'  => ['label' => 'Fecha Creación'],
            'updated_at'  => ['label' => 'Última Actualización'],
        ]);
    }

    protected function filterMap(): array
    {
        return [
            'search'    => fn (Builder $q, $v) => $q->where('name', 'like', "%{$v}%"),
            'type'      => fn (Builder $q, $v) => $q->where('type', $v),
            'is_active' => fn (Builder $q, $v) => $q->where('is_active', (bool) $v),
        ];
    }

    protected function filterOptions(): array
    {
        return app(WarehouseCatalogService::class)->getForIndex();
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'type'      => $options['types'][$value] ?? $value,
            'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default     => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? Warehouse::onlyTrashed()
            : Warehouse::query();

        return $this->applyFilters($query->withIndexRelations());
    }

    public function toggleActivo(int $id): void
    {
        abort_unless(auth()->user()->can('warehouses.manage'), 403);

        $warehouse = Warehouse::findOrFail($id);
        $activo = $warehouse->toggleActivo();

        $this->notify('success', 'Almacén "'.$warehouse->name.'" '.($activo ? 'activado' : 'desactivado').' correctamente.');
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('warehouses.manage'), 403);

        $warehouse = Warehouse::onlyTrashed()->findOrFail($id);
        $warehouse->restore();

        $this->notify('success', "Almacén \"{$warehouse->name}\" restaurado correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('warehouses.manage'), 403);

        $warehouse = Warehouse::onlyTrashed()->findOrFail($id);
        $name = $warehouse->name;
        $warehouse->forceDelete();

        $this->notify('success', "Almacén \"{$name}\" eliminado definitivamente.");
    }

    public function render()
    {
        $warehouses = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.inventory.warehouse-table', array_merge(
            ['warehouses' => $warehouses],
            $this->filterOptions()
        ));
    }
}
