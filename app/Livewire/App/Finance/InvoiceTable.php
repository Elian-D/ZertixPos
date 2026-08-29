<?php

namespace App\Livewire\App\Finance;

use App\Exports\Sales\InvoicesExport;
use App\Livewire\Base\DataTable;
use App\Models\Sales\Invoice;
use App\Services\Sales\InvoicesServices\InvoiceCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — Invoice::status solo
 * refleja que la venta origen fue anulada (InvoiceService.php:35, disparado
 * desde SaleService::cancel()), no un cancel() propio. El controlador viejo ni
 * siquiera implementaba destroy() pese a usar SoftDeletesTrait. Solo lectura.
 */
class InvoiceTable extends DataTable
{
    public array $filters = [
        'search'      => '',
        'client_id'   => '',
        'type'        => '',
        'status'      => '',
        'format_type' => '',
        'from_date'   => '',
        'to_date'     => '',
    ];

    protected function columns(): array
    {
        return [
            'invoice_number' => ['label' => 'N° Factura', 'default' => true, 'mobile' => true],
            'sale_id'        => ['label' => 'Venta Origen'],
            'client_id'      => ['label' => 'Cliente', 'default' => true],
            'type'           => ['label' => 'Tipo Venta', 'default' => true],
            'format_type'    => ['label' => 'Formato', 'default' => true],
            'total_amount'   => ['label' => 'Monto Total', 'default' => true, 'mobile' => true],
            'status'         => ['label' => 'Estado Doc.', 'default' => true, 'mobile' => true],
            'due_date'       => ['label' => 'Vencimiento'],
            'generated_by'   => ['label' => 'Emitido por'],
            'created_at'     => ['label' => 'Fecha Emisión', 'default' => true],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('invoice_number', 'like', "%{$v}%")
                ->orWhereHas('sale.client', fn (Builder $sq) => $sq->where('name', 'like', "%{$v}%"))),
            'client_id'   => fn (Builder $q, $v) => $q->whereHas('sale', fn (Builder $sq) => $sq->where('client_id', $v)),
            'type'        => fn (Builder $q, $v) => $q->where('type', $v),
            'status'      => fn (Builder $q, $v) => $q->where('status', $v),
            'format_type' => fn (Builder $q, $v) => $q->where('format_type', $v),
            'from_date'   => fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfMinute()),
            'to_date'     => fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfMinute()),
        ];
    }

    protected function filterOptions(): array
    {
        return app(InvoiceCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'client_id'   => $options['clients']->firstWhere('id', $value)?->name ?? $value,
            'type'        => $options['payment_types'][$value] ?? $value,
            'status'      => $options['statuses'][$value] ?? $value,
            'format_type' => $options['formats'][$value] ?? $value,
            default       => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(Invoice::query()->withIndexRelations());
    }

    public function export()
    {
        abort_unless(auth()->user()->can('invoices.export'), 403);

        return Excel::download(
            new InvoicesExport($this->baseQuery()),
            'historial-facturacion-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function render()
    {
        $invoices = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.finance.invoice-table', array_merge(
            ['items' => $invoices],
            $this->filterOptions()
        ));
    }
}
