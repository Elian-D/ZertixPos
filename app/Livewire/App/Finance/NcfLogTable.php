<?php

namespace App\Livewire\App\Finance;

use App\Exports\Sales\Ncf\NcfLogsExport;
use App\Livewire\Base\DataTable;
use App\Models\Sales\Ncf\NcfLog;
use App\Services\Sales\Ncf\NcfCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — bitácora fiscal de
 * comprobantes emitidos, exigida por la DGII (reporte 607). Sin SoftDeletes,
 * sin destroy — nunca existió una ruta de borrado para este modelo.
 */
class NcfLogTable extends DataTable
{
    public array $filters = [
        'search'      => '',
        'ncf_type_id' => '',
        'status'      => '',
        'from_date'   => '',
        'to_date'     => '',
    ];

    protected function columns(): array
    {
        return [
            'full_ncf'            => ['label' => 'NCF', 'default' => true, 'mobile' => true],
            'type_id'             => ['label' => 'Tipo', 'default' => true],
            'sale_number'         => ['label' => 'Venta #', 'default' => true],
            'customer'            => ['label' => 'Cliente', 'default' => true],
            'customer_rnc'        => ['label' => 'RNC/Cédula'],
            'status'              => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'cancellation_reason' => ['label' => 'Motivo Anulación'],
            'user_id'             => ['label' => 'Usuario'],
            'created_at'          => ['label' => 'Fecha/Hora', 'default' => true],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search'      => fn (Builder $q, $v) => $q->where('full_ncf', 'like', "%{$v}%"),
            'ncf_type_id' => fn (Builder $q, $v) => $q->where('ncf_type_id', $v),
            'status'      => fn (Builder $q, $v) => $q->where('ncf_logs.status', $v),
            'from_date'   => fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfMinute()->toDateTimeString()),
            'to_date'     => fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfMinute()->toDateTimeString()),
        ];
    }

    protected function filterOptions(): array
    {
        return app(NcfCatalogService::class)->getForLogs();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'ncf_type_id' => $options['ncf_types'][$value] ?? $value,
            'status'      => $options['statuses'][$value] ?? $value,
            default       => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        // 'sale.invoice' (no solo 'sale'): el modal de detalle enlaza a la
        // factura real de la venta — sin este eager load es un N+1 por fila
        // con NCF usado, y sin la relación correcta (Sale::invoice, no
        // sale_id === invoice_id) el link apuntaba a un id equivocado (404
        // real detectado en producción, corregido acá).
        return $this->applyFilters(
            NcfLog::query()->with(['sale.client', 'sale.invoice', 'type', 'user'])
        );
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()->can('manage ncf sequences'), 403);

        return Excel::download(
            new NcfLogsExport($this->baseQuery()),
            'auditoria-ncf-'.now()->format('Ymd-Hi').'.xlsx'
        );
    }

    public function render()
    {
        $logs = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.finance.ncf-log-table', array_merge(
            ['items' => $logs],
            $this->filterOptions()
        ));
    }
}
