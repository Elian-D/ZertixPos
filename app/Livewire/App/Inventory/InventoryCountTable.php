<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Inventory\InventoryCount;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Listado de tomas físicas (v1.5.0 REQ-2.1). Crear es un modal de este mismo índice
 * (almacén, alcance, conteo ciego y notas); el conteo, la revisión y el PDF viven en
 * InventoryCountController.
 */
class InventoryCountTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'warehouse_id' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'warehouse' => ['label' => 'Almacén', 'default' => true, 'mobile' => true],
            'scope' => ['label' => 'Alcance', 'default' => true],
            'progress' => ['label' => 'Contados', 'default' => true],
            'difference_value' => ['label' => 'Valor ajustado', 'default' => true],
            'status' => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'creator' => ['label' => 'Creada por'],
            'created_at' => ['label' => 'Fecha', 'default' => true],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'almacén'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhereHas('warehouse', fn (Builder $w) => $w->where('name', 'like', "%{$v}%"))),
            'warehouse_id' => fn (Builder $q, $v) => $q->where('warehouse_id', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date' => fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfMinute()),
            'to_date' => fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfMinute()),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => InventoryCount::getStatuses(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'warehouse_id' => $options['warehouses'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = InventoryCount::query()->withIndexRelations();

        // El contador (solo `inventory_counts.count`) ve únicamente las tomas que puede
        // contar: las aplicadas/canceladas no las podría abrir.
        if (! auth()->user()->can('inventory_counts.view')) {
            $query->where('status', InventoryCount::STATUS_DRAFT);
        }

        return $this->applyFilters($query);
    }

    public function render()
    {
        return view('livewire.app.inventory.inventory-count-table', array_merge(
            [
                'counts' => $this->baseQuery()->latest()->paginate($this->perPage),
                // Para el modal de crear.
                'categories' => Category::activos()->orderBy('name')->pluck('name', 'id')->all(),
            ],
            $this->filterOptions()
        ));
    }
}
