<?php

namespace App\Livewire\App\Finance;

use App\Exports\Accounting\CollectionsExport;
use App\Livewire\Base\DataTable;
use App\Models\Accounting\ClientCollection;
use App\Services\Accounting\Collection\CollectionCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — un Cobro es la
 * bitácora de dinero recibido de un cliente; nunca se borra ni se archiva,
 * ni siquiera anulado. `cancel()` (real, revierte el saldo de la CxC y su
 * asiento contable) sigue siendo una ruta real fuera de este componente —
 * mismo criterio aplicado a Receivable al migrar Finanzas (REQ-0.9).
 */
class CollectionTable extends DataTable
{
    public array $filters = [
        'search'       => '',
        'client_id'    => '',
        'tipo_pago_id' => '',
        'status'       => '',
        'from_date'    => '',
        'to_date'      => '',
        'min_amount'   => '',
        'max_amount'   => '',
    ];

    protected function columns(): array
    {
        return [
            'payment_date'   => ['label' => 'Fecha', 'default' => true],
            'receipt_number' => ['label' => 'No. Recibo', 'default' => true, 'mobile' => true],
            'client'         => ['label' => 'Cliente', 'default' => true, 'mobile' => true],
            'receivable'     => ['label' => 'Factura/Doc', 'default' => true],
            'tipo_pago'      => ['label' => 'Método', 'default' => true],
            'reference'      => ['label' => 'Referencia'],
            'amount'         => ['label' => 'Monto Cobrado', 'default' => true, 'mobile' => true],
            'status'         => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_by'     => ['label' => 'Registrado por'],
            'created_at'     => ['label' => 'Fecha Registro'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('receipt_number', 'like', "%{$v}%")
                ->orWhere('reference', 'like', "%{$v}%")
                ->orWhere('note', 'like', "%{$v}%")),
            'client_id'    => fn (Builder $q, $v) => $q->where('client_id', $v),
            'tipo_pago_id' => fn (Builder $q, $v) => $q->where('tipo_pago_id', $v),
            'status'       => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date'    => fn (Builder $q, $v) => $q->where('payment_date', '>=', Carbon::parse($v)->startOfMinute()->toDateTimeString()),
            'to_date'      => fn (Builder $q, $v) => $q->where('payment_date', '<=', Carbon::parse($v)->endOfMinute()->toDateTimeString()),
            'min_amount'   => fn (Builder $q, $v) => $q->where('amount', '>=', $v),
            'max_amount'   => fn (Builder $q, $v) => $q->where('amount', '<=', $v),
        ];
    }

    protected function filterOptions(): array
    {
        return app(CollectionCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'client_id'    => $options['clients']->firstWhere('id', $value)?->name ?? $value,
            'tipo_pago_id' => $options['paymentMethods']->firstWhere('id', $value)?->nombre ?? $value,
            'status'       => $options['statuses'][$value] ?? $value,
            default        => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(
            ClientCollection::query()->with(['client', 'receivable', 'tipoPago', 'creator'])
        );
    }

    public function export()
    {
        abort_unless(auth()->user()->can('export payments'), 403);

        return Excel::download(
            new CollectionsExport($this->baseQuery()),
            'reporte-cobros-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function render()
    {
        $collections = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.finance.collection-table', array_merge(
            ['items' => $collections],
            $this->filterOptions()
        ));
    }
}
