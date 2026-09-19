<?php

namespace App\Livewire\App\Clients;

use App\Livewire\Base\DataTable;
use App\Models\Sales\Quotes\Quote;
use App\Services\Sales\Quotes\QuoteCatalogService;
use App\Services\Sales\SalesServices\SaleCatalogService;
use Illuminate\Database\Eloquent\Builder;

class QuoteTable extends DataTable
{
    public array $filters = [
        'search'      => '',
        'customer_id' => '',
        'status'      => '',
        'origin'      => '',
        'user_id'     => '',
        'from_date'   => '',
        'to_date'     => '',
    ];

    protected function columns(): array
    {
        return [
            'id'              => ['label' => 'ID', 'mobile' => true],
            'created_at'      => ['label' => 'Fecha Emisión', 'default' => true],
            'customer_id'     => ['label' => 'Cliente', 'default' => true, 'mobile' => true],
            'user_id'         => ['label' => 'Vendedor'],
            'origin'          => ['label' => 'Origen', 'default' => true],
            'status'          => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'total'           => ['label' => 'Monto Total', 'default' => true, 'mobile' => true],
            'expires_at'      => ['label' => 'Vencimiento', 'default' => true],
            'sale_id'         => ['label' => 'Venta Ref.', 'default' => true],
            'pos_terminal_id' => ['label' => 'Terminal'],
            'notes'           => ['label' => 'Observaciones'],
            'updated_at'      => ['label' => 'Última Modificación'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('id', 'like', "%{$v}%")
                ->orWhereHas('customer', fn (Builder $sq) => $sq->where('name', 'like', "%{$v}%"))),
            'customer_id' => fn (Builder $q, $v) => $q->where('customer_id', $v),
            'status'      => fn (Builder $q, $v) => $q->where('status', $v),
            'origin'      => fn (Builder $q, $v) => $q->where('origin', $v),
            'user_id'     => fn (Builder $q, $v) => $q->where('user_id', $v),
            'from_date'   => fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v),
            'to_date'     => fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v),
        ];
    }

    protected function filterOptions(): array
    {
        return app(QuoteCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'customer_id' => $options['customers']->firstWhere('id', $value)?->name ?? $value,
            'user_id'     => $options['users']->firstWhere('id', $value)?->name ?? $value,
            'status'      => $options['statuses'][$value] ?? $value,
            'origin'      => $options['origins'][$value] ?? $value,
            default       => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        // 'sale.invoice' anidado (no solo 'sale') — la vista lee
        // $quote->sale->invoice->id para el link a la factura; sin el nested
        // eager load es un N+1 por fila con venta convertida.
        return $this->applyFilters(
            Quote::query()->with(['customer', 'user', 'sale.invoice', 'terminal'])
        );
    }

    public function render()
    {
        $quotes = $this->baseQuery()->latest()->paginate($this->perPage);

        $saleCatalogs = app(SaleCatalogService::class)->getForForm();

        return view('livewire.app.clients.quote-table', array_merge(
            ['quotes' => $quotes],
            $this->filterOptions(),
            [
                'tipo_pagos' => $saleCatalogs['tipo_pagos'],
                'ncf_types'  => $saleCatalogs['ncf_types'],
                'warehouses' => $saleCatalogs['warehouses'],
            ]
        ));
    }
}
