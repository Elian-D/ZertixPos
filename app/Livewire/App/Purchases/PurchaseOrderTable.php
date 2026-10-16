<?php

namespace App\Livewire\App\Purchases;

use App\Livewire\Base\DataTable;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Listado de órdenes de compra (v1.5.0 REQ-5.1). */
class PurchaseOrderTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'vendor_id' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'order_date' => ['label' => 'Fecha', 'default' => true],
            'vendor' => ['label' => 'Proveedor', 'default' => true, 'mobile' => true],
            'warehouse' => ['label' => 'Almacén', 'default' => true],
            'condition' => ['label' => 'Condición'],
            'expected_date' => ['label' => 'Entrega estimada'],
            'items' => ['label' => 'Líneas'],
            'total' => ['label' => 'Total', 'default' => true, 'mobile' => true],
            'status' => ['label' => 'Estado', 'default' => true, 'mobile' => true],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'proveedor', 'producto'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhereHas('vendor', fn (Builder $w) => $w->where('business_name', 'like', "%{$v}%")->orWhere('trade_name', 'like', "%{$v}%"))
                ->orWhereHas('items.product', fn (Builder $p) => $p->where('name', 'like', "%{$v}%"))),
            'vendor_id' => fn (Builder $q, $v) => $q->where('vendor_id', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('order_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('order_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'vendors' => Vendor::orderBy('business_name')->get(['id', 'business_name', 'trade_name'])
                ->mapWithKeys(fn ($v) => [$v->id => $v->display_name])->all(),
            'statuses' => PurchaseOrder::getStatuses(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'vendor_id' => $options['vendors'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(PurchaseOrder::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.purchases.purchase-order-table', array_merge(
            [
                'orders' => $this->baseQuery()->orderByDesc('order_date')->orderByDesc('id')->paginate($this->perPage),
                'toReceive' => PurchaseOrder::whereIn('status', PurchaseOrder::RECEIVABLE_STATUSES)->count(),
            ],
            $this->filterOptions()
        ));
    }
}
