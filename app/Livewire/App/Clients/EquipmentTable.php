<?php

namespace App\Livewire\App\Clients;

use App\Exports\Equipment\EquipmentsExport;
use App\Livewire\Base\DataTable;
use App\Models\Clients\Equipment;
use App\Services\Equipment\EquipmentCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class EquipmentTable extends DataTable
{
    public array $filters = [
        'search'            => '',
        'equipment_type_id' => '',
        'point_of_sale_id'  => '',
        'active'            => '',
        'trashed'           => '',
    ];

    protected function columns(): array
    {
        return [
            'code'              => ['label' => 'Código', 'default' => true, 'mobile' => true],
            'name'              => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'equipment_type_id' => ['label' => 'Tipo de Equipo', 'default' => true],
            'point_of_sale_id'  => ['label' => 'Punto de Venta', 'default' => true],
            'serial_number'     => ['label' => 'Serial'],
            'model'             => ['label' => 'Modelo'],
            'active'            => ['label' => 'Estado', 'default' => true],
            'created_at'        => ['label' => 'Fecha Creación'],
            'updated_at'        => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('code', 'like', "%{$v}%")
                ->orWhere('name', 'like', "%{$v}%")
                ->orWhere('serial_number', 'like', "%{$v}%")
                ->orWhere('model', 'like', "%{$v}%")),
            'equipment_type_id' => fn (Builder $q, $v) => $q->where('equipment_type_id', $v),
            'point_of_sale_id'  => fn (Builder $q, $v) => $q->where('point_of_sale_id', $v),
            'active'            => fn (Builder $q, $v) => $q->where('active', filter_var($v, FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    protected function filterOptions(): array
    {
        return app(EquipmentCatalogService::class)->getForFilters();
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'equipment_type_id' => $options['equipmentTypes']->firstWhere('id', $value)?->nombre ?? $value,
            'point_of_sale_id'  => $options['pointsOfSale']->firstWhere('id', $value)?->name ?? $value,
            'active'            => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default             => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? Equipment::onlyTrashed()
            : Equipment::query();

        return $this->applyFilters($query->withIndexRelations());
    }

    public function export()
    {
        return Excel::download(
            new EquipmentsExport($this->baseQuery()),
            'equipos-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('equipment restore'), 403);

        $equipment = Equipment::onlyTrashed()->findOrFail($id);
        $equipment->restore();

        $this->notify('success', "Equipo \"{$equipment->code}\" restaurado correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment delete'), 403);

        $equipment = Equipment::onlyTrashed()->findOrFail($id);
        $code = $equipment->code;
        $equipment->forceDelete();

        $this->notify('success', "Equipo \"{$code}\" eliminado definitivamente.");
    }

    public function render()
    {
        $equipments = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.clients.equipment-table', array_merge(
            ['equipments' => $equipments],
            $this->filterOptions()
        ));
    }
}
