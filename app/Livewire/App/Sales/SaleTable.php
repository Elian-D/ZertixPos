<?php

namespace App\Livewire\App\Sales;

use App\Exports\Sales\SalesExport;
use App\Livewire\Base\DataTable;
use App\Models\Sales\Sale;
use App\Services\Sales\SalesServices\SaleCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class SaleTable extends DataTable
{
    public array $filters = [
        'search'          => '',
        'client_id'       => '',
        'warehouse_id'    => '',
        'payment_type'    => '',
        'tipo_pago_id'    => '',
        'status'          => '',
        'from_date'       => '',
        'to_date'         => '',
        'min_amount'      => '',
        'max_amount'      => '',
        'pos_session_id'  => '',
        'pos_terminal_id' => '',
    ];

    protected function columns(): array
    {
        return [
            'sale_date'       => ['label' => 'Fecha', 'default' => true, 'mobile' => true],
            'number'          => ['label' => 'Folio / Número', 'default' => true],
            'client_id'       => ['label' => 'Cliente', 'default' => true, 'mobile' => true],
            'warehouse_id'    => ['label' => 'Almacén'],
            'pos_terminal_id' => ['label' => 'Terminal POS', 'default' => true],
            'pos_session_id'  => ['label' => 'Sesión POS'],
            'payment_type'    => ['label' => 'Tipo de Venta', 'default' => true],
            'tipo_pago_id'    => ['label' => 'Método de Pago', 'default' => true],
            'total_amount'    => ['label' => 'Total', 'default' => true, 'mobile' => true],
            'status'          => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'user_id'         => ['label' => 'Vendedor'],
            'notes'           => ['label' => 'Notas'],
            'created_at'      => ['label' => 'Fecha Registro'],
            'updated_at'      => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhere('notes', 'like', "%{$v}%")
                ->orWhereHas('client', fn (Builder $sq) => $sq->where('name', 'like', "%{$v}%"))),
            'client_id'       => fn (Builder $q, $v) => $q->where('client_id', $v),
            'warehouse_id'    => fn (Builder $q, $v) => $q->where('warehouse_id', $v),
            'payment_type'    => fn (Builder $q, $v) => $q->where('payment_type', $v),
            'tipo_pago_id'    => fn (Builder $q, $v) => $q->where('tipo_pago_id', $v),
            'status'          => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date'       => fn (Builder $q, $v) => $q->where('sale_date', '>=', \Illuminate\Support\Carbon::parse($v)->startOfMinute()),
            'to_date'         => fn (Builder $q, $v) => $q->where('sale_date', '<=', \Illuminate\Support\Carbon::parse($v)->endOfMinute()),
            'min_amount'      => fn (Builder $q, $v) => $q->where('total_amount', '>=', $v),
            'max_amount'      => fn (Builder $q, $v) => $q->where('total_amount', '<=', $v),
            'pos_session_id'  => fn (Builder $q, $v) => $q->where('pos_session_id', $v),
            'pos_terminal_id' => fn (Builder $q, $v) => $q->where('pos_terminal_id', $v),
        ];
    }

    protected function filterOptions(): array
    {
        return app(SaleCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'client_id'       => $options['clients']->firstWhere('id', $value)?->name ?? $value,
            'warehouse_id'    => $options['warehouses']->firstWhere('id', $value)?->name ?? $value,
            'payment_type'    => $options['payment_types'][$value] ?? $value,
            'tipo_pago_id'    => $options['tipo_pagos']->firstWhere('id', $value)?->nombre ?? $value,
            'status'          => $options['statuses'][$value] ?? $value,
            'pos_terminal_id' => $options['pos_terminals'][$value] ?? $value,
            default           => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(Sale::query()->withIndexRelations());
    }

    public function export()
    {
        return Excel::download(
            new SalesExport($this->baseQuery()),
            'reporte-ventas-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function render()
    {
        $sales = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.sales.sale-table', array_merge(
            ['sales' => $sales],
            $this->filterOptions()
        ));
    }
}
