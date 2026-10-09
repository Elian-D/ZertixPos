<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Inventory\InventoryWaste;
use App\Models\Inventory\InventoryWasteItem;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Listado de mermas (v1.5.0 REQ-2.2). Filtra por almacén, motivo, estado y fecha, y
 * muestra el valor perdido de lo filtrado: es la base de "cuánto pierdo por mes".
 */
class InventoryWasteTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'warehouse_id' => '',
        'reason' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'waste_date' => ['label' => 'Fecha', 'default' => true],
            'warehouse' => ['label' => 'Almacén', 'default' => true, 'mobile' => true],
            'reasons' => ['label' => 'Motivos', 'default' => true],
            'items' => ['label' => 'Líneas'],
            'total_value' => ['label' => 'Valor perdido', 'default' => true, 'mobile' => true],
            'status' => ['label' => 'Estado', 'default' => true],
            'creator' => ['label' => 'Registró'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'almacén', 'producto'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhereHas('warehouse', fn (Builder $w) => $w->where('name', 'like', "%{$v}%"))
                ->orWhereHas('items.product', fn (Builder $p) => $p->where('name', 'like', "%{$v}%"))),
            'warehouse_id' => fn (Builder $q, $v) => $q->where('warehouse_id', $v),
            'reason' => fn (Builder $q, $v) => $q->whereHas('items', fn (Builder $i) => $i->where('reason', $v)),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('waste_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('waste_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'reasons' => InventoryWasteItem::getReasons(),
            'statuses' => InventoryWaste::getStatuses(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'warehouse_id' => $options['warehouses'][$value] ?? $value,
            'reason' => $options['reasons'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(InventoryWaste::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.inventory.inventory-waste-table', array_merge(
            [
                'wastes' => $this->baseQuery()->orderByDesc('waste_date')->orderByDesc('id')->paginate($this->perPage),
                // Valor perdido de lo filtrado (solo aplicadas: una anulada devolvió el stock).
                'lostTotal' => (float) $this->baseQuery()->where('status', InventoryWaste::STATUS_APPLIED)->sum('total_value'),
            ],
            $this->filterOptions()
        ));
    }
}
