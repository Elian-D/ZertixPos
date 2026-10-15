<?php

namespace App\Livewire\App\Purchases;

use App\Livewire\Base\DataTable;
use App\Models\Purchases\PurchaseReception;
use App\Models\Purchases\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Listado de recepciones de compra (v1.5.0 REQ-5.2): se crean desde la orden. */
class PurchaseReceptionTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'vendor_id' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'reception_date' => ['label' => 'Fecha', 'default' => true],
            'vendor' => ['label' => 'Proveedor', 'default' => true, 'mobile' => true],
            'order' => ['label' => 'Orden', 'default' => true],
            'invoice' => ['label' => 'Factura'],
            'quantities' => ['label' => 'Recibido / rechazado', 'default' => true],
            'total' => ['label' => 'Total', 'default' => true, 'mobile' => true],
            'payable' => ['label' => 'Cuenta por pagar', 'default' => true],
            'rating' => ['label' => 'Calificación'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'orden', 'factura', 'proveedor'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhere('supplier_invoice', 'like', "%{$v}%")
                ->orWhere('supplier_ncf', 'like', "%{$v}%")
                ->orWhereHas('order', fn (Builder $o) => $o->where('number', 'like', "%{$v}%"))
                ->orWhereHas('order.vendor', fn (Builder $w) => $w->where('business_name', 'like', "%{$v}%")->orWhere('trade_name', 'like', "%{$v}%"))),
            'vendor_id' => fn (Builder $q, $v) => $q->whereHas('order', fn (Builder $o) => $o->where('vendor_id', $v)),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('reception_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('reception_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'vendors' => Vendor::orderBy('business_name')->get(['id', 'business_name', 'trade_name'])
                ->mapWithKeys(fn ($v) => [$v->id => $v->display_name])->all(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'vendor_id' => $this->filterOptions()['vendors'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(PurchaseReception::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.purchases.purchase-reception-table', array_merge(
            [
                'receptions' => $this->baseQuery()->orderByDesc('reception_date')->orderByDesc('id')->paginate($this->perPage),
                'receivedTotal' => (float) $this->baseQuery()->sum('total'),
            ],
            $this->filterOptions()
        ));
    }
}
