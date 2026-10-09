<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Inventory\InventoryTransfer;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Listado de transferencias entre almacenes (v1.5.0 REQ-2.4). "En tránsito" es lo que
 * salió del origen y todavía no se recibe.
 */
class InventoryTransferTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'from_warehouse_id' => '',
        'to_warehouse_id' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'route' => ['label' => 'Desde → Hacia', 'default' => true, 'mobile' => true],
            'items' => ['label' => 'Líneas', 'default' => true],
            'status' => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'sent_at' => ['label' => 'Enviada', 'default' => true],
            'received_at' => ['label' => 'Recibida'],
            'creator' => ['label' => 'Creó'],
            'created_at' => ['label' => 'Creada', 'default' => true],
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
                ->orWhereHas('fromWarehouse', fn (Builder $w) => $w->where('name', 'like', "%{$v}%"))
                ->orWhereHas('toWarehouse', fn (Builder $w) => $w->where('name', 'like', "%{$v}%"))
                ->orWhereHas('items.product', fn (Builder $p) => $p->where('name', 'like', "%{$v}%"))),
            'from_warehouse_id' => fn (Builder $q, $v) => $q->where('from_warehouse_id', $v),
            'to_warehouse_id' => fn (Builder $q, $v) => $q->where('to_warehouse_id', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date' => fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()),
            'to_date' => fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => InventoryTransfer::getStatuses(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'from_warehouse_id', 'to_warehouse_id' => $options['warehouses'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(InventoryTransfer::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.inventory.inventory-transfer-table', array_merge(
            [
                'transfers' => $this->baseQuery()->latest()->paginate($this->perPage),
                'inTransit' => InventoryTransfer::where('status', InventoryTransfer::STATUS_SENT)->count(),
            ],
            $this->filterOptions()
        ));
    }
}
