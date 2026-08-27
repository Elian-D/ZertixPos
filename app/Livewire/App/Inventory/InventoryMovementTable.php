<?php

namespace App\Livewire\App\Inventory;

use App\Exports\Inventory\MovementsExport;
use App\Livewire\Base\DataTable;
use App\Models\Inventory\InventoryMovement;
use App\Services\Inventory\MovementCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class InventoryMovementTable extends DataTable
{
    public array $filters = [
        'search'       => '',
        'warehouse_id' => '',
        'type'         => '',
        'from_date'    => '',
        'to_date'      => '',
    ];

    protected function columns(): array
    {
        return [
            'created_at'  => ['label' => 'Fecha/Hora', 'default' => true],
            'product'     => ['label' => 'Producto', 'default' => true, 'mobile' => true],
            'warehouse'   => ['label' => 'Almacén', 'default' => true],
            'type'        => ['label' => 'Operación', 'default' => true, 'mobile' => true],
            'toWarehouse' => ['label' => 'Almacén de Destino'],
            'quantity'    => ['label' => 'Cant.', 'default' => true, 'mobile' => true],
            'balance'     => ['label' => 'Balance', 'default' => true],
            'user'        => ['label' => 'Responsable', 'default' => true],
            'reference'   => ['label' => 'Documento/Ref'],
            'description' => ['label' => 'Observaciones'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->whereHas('product', fn (Builder $p) => $p->where('name', 'like', "%{$v}%"))
                ->orWhere('description', 'like', "%{$v}%")),
            'warehouse_id' => fn (Builder $q, $v) => $q->where('warehouse_id', $v),
            'type'         => fn (Builder $q, $v) => $q->where('type', $v),
            'from_date'    => fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfMinute()),
            'to_date'      => fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfMinute()),
        ];
    }

    protected function filterOptions(): array
    {
        return app(MovementCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'warehouse_id' => $options['warehouses']->firstWhere('id', $value)?->name ?? $value,
            'type'         => $options['types'][$value] ?? $value,
            default        => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(InventoryMovement::query()->withIndexRelations());
    }

    public function export()
    {
        abort_unless(auth()->user()->can('view inventory movements'), 403);

        return Excel::download(
            new MovementsExport($this->baseQuery()),
            'movimientos-inventario-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function render()
    {
        $movements = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.inventory.inventory-movement-table', array_merge(
            ['movements' => $movements],
            $this->filterOptions()
        ));
    }
}
