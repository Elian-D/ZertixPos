<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Accounting\Receivable;
use App\Services\Accounting\Receivable\ReceivableCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — CxC es la bitácora de
 * deuda de un cliente, nunca se borra ni se archiva. El estado `cancelled` solo
 * refleja que la venta/documento origen fue anulado (reversión real en
 * SaleService::cancel()) — la fila de Receivable se conserva siempre para que
 * el historial de deudas (incluidas las anuladas) siga siendo consultable.
 * Sin destroy()/restore()/forceDelete(), sin tab de Papelera.
 */
class ReceivableTable extends DataTable
{
    public array $filters = [
        'search'      => '',
        'status'      => '',
        'client_id'   => '',
        'overdue'     => '',
        'min_balance' => '',
        'max_balance' => '',
    ];

    protected function columns(): array
    {
        return array_filter([
            'emission_date'          => ['label' => 'Fecha Emisión', 'default' => true],
            'due_date'                => ['label' => 'Vencimiento', 'default' => true],
            'document_number'         => ['label' => 'No. Factura', 'default' => true, 'mobile' => true],
            'client'                  => ['label' => 'Cliente', 'default' => true, 'mobile' => true],
            'description'             => ['label' => 'Concepto'],
            'total_amount'            => ['label' => 'Monto Original', 'default' => true],
            'current_balance'         => ['label' => 'Saldo Pendiente', 'default' => true, 'mobile' => true],
            'accounting_account_id'   => module_enabled('accounting.advanced')
                ? ['label' => 'Cuenta Contable', 'default' => true]
                : null,
            'status'                  => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'updated_at'              => ['label' => 'Último Movimiento'],
        ]);
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('document_number', 'like', "%{$v}%")
                ->orWhere('description', 'like', "%{$v}%")),
            'status'    => fn (Builder $q, $v) => $q->where('status', $v),
            'client_id' => fn (Builder $q, $v) => $q->where('client_id', $v),
            'overdue'   => fn (Builder $q, $v) => $v === 'yes'
                ? $q->where('due_date', '<', Carbon::now()->format('Y-m-d'))->where('status', '!=', 'paid')
                : $q->where('due_date', '>=', Carbon::now()->format('Y-m-d')),
            'min_balance' => fn (Builder $q, $v) => $q->where('current_balance', '>=', $v),
            'max_balance' => fn (Builder $q, $v) => $q->where('current_balance', '<=', $v),
        ];
    }

    protected function filterOptions(): array
    {
        return app(ReceivableCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'client_id' => $options['clients']->firstWhere('id', $value)?->name ?? $value,
            'status'    => $options['statuses'][$value] ?? $value,
            'overdue'   => $value === 'yes' ? 'Vencidas' : 'Al día',
            default     => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        // 'client.accountingAccount'/'accountingAccount' solo se piden con
        // accounting.advanced activo — son la fuente de la columna
        // 'accounting_account_id' (ver columns()), que ni siquiera existe si el
        // módulo está apagado. Cargarlas siempre generaba 2 queries reales a
        // accounting_accounts en cada carga de la tabla sin que nada las usara
        // (detectado por duplicado idéntico en Debugbar).
        $relations = array_filter([
            'client' . (module_enabled('accounting.advanced') ? '.accountingAccount' : ''),
            module_enabled('accounting.advanced') ? 'accountingAccount' : null,
            'journalEntry',
        ]);

        return $this->applyFilters(Receivable::query()->with($relations));
    }

    public function render()
    {
        $receivables = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.finance.receivable-table', array_merge(
            ['items' => $receivables],
            $this->filterOptions()
        ));
    }
}
