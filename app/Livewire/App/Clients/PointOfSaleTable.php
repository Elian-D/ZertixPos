<?php

namespace App\Livewire\App\Clients;

use App\Exports\PointOfSale\PointsOfSaleExport;
use App\Livewire\Base\DataTable;
use App\Models\Clients\PointOfSale;
use App\Services\PointOfSale\POSCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class PointOfSaleTable extends DataTable
{
    public array $filters = [
        'search'        => '',
        'client'        => '',
        'business_type' => '',
        'state'         => '',
        'active'        => '',
        'trashed'       => '',
    ];

    protected function columns(): array
    {
        return [
            'name'              => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'client_id'         => ['label' => 'Cliente', 'default' => true],
            'business_type_id'  => ['label' => 'Tipo Negocio', 'default' => true],
            'provincia_id'      => ['label' => 'Provincia'],
            'city'              => ['label' => 'Ciudad'],
            'contact_name'      => ['label' => 'Contacto'],
            'contact_phone'     => ['label' => 'Teléfono Contacto'],
            'active'            => ['label' => 'Estado', 'default' => true],
            'created_at'        => ['label' => 'Fecha Creación'],
            'updated_at'        => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('name', 'like', "%{$v}%")
                ->orWhere('contact_name', 'like', "%{$v}%")),
            'client'        => fn (Builder $q, $v) => $q->where('client_id', $v),
            'business_type' => fn (Builder $q, $v) => $q->where('business_type_id', $v),
            'state'         => fn (Builder $q, $v) => $q->where('provincia_id', $v),
            'active'        => fn (Builder $q, $v) => $q->where('active', filter_var($v, FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    protected function filterOptions(): array
    {
        return app(POSCatalogService::class)->getForFilters();
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'client'        => $options['clients']->firstWhere('id', $value)?->name ?? $value,
            'business_type' => $options['businessTypes']->firstWhere('id', $value)?->nombre ?? $value,
            'state'         => $options['states']->firstWhere('id', $value)?->name ?? $value,
            'active'        => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default         => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? PointOfSale::onlyTrashed()
            : PointOfSale::query();

        return $this->applyFilters($query->withIndexRelations());
    }

    public function export()
    {
        return Excel::download(
            new PointsOfSaleExport($this->baseQuery()),
            'puntos-de-venta-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('delivery_points.restore'), 403);

        $pos = PointOfSale::onlyTrashed()->findOrFail($id);
        $pos->restore();

        $this->notify('success', "Punto de venta \"{$pos->name}\" restaurado correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('delivery_points.delete'), 403);

        $pos = PointOfSale::onlyTrashed()->findOrFail($id);
        $name = $pos->name;
        $pos->forceDelete();

        $this->notify('success', "Punto de venta \"{$name}\" eliminado definitivamente.");
    }

    public function render()
    {
        $pos = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.clients.point-of-sale-table', array_merge(
            ['pos' => $pos],
            $this->filterOptions()
        ));
    }
}
