<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Accounting\Payable;
use App\Models\Purchases\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Cuentas por pagar (v1.5.0 REQ-4.2): lo que se debe, con su vencimiento. Arriba, el total
 * pendiente de lo filtrado.
 */
class PayableTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'vendor_id' => '',
        'status' => '',
        'overdue' => false,
        'source_type' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'vendor' => ['label' => 'Proveedor', 'default' => true, 'mobile' => true],
            'concept' => ['label' => 'Concepto', 'default' => true],
            'origin' => ['label' => 'Origen'],
            'issue_date' => ['label' => 'Emitida'],
            'due_date' => ['label' => 'Vence', 'default' => true],
            'total_amount' => ['label' => 'Total', 'default' => true],
            'balance' => ['label' => 'Pendiente', 'default' => true, 'mobile' => true],
            'status' => ['label' => 'Estado', 'default' => true],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'proveedor', 'concepto', 'factura'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhere('concept', 'like', "%{$v}%")
                ->orWhere('supplier_invoice', 'like', "%{$v}%")
                ->orWhereHas('vendor', fn (Builder $w) => $w->where('business_name', 'like', "%{$v}%")->orWhere('trade_name', 'like', "%{$v}%"))),
            'vendor_id' => fn (Builder $q, $v) => $q->where('vendor_id', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            // filled(false) es true para el motor: el toggle apagado no debe filtrar.
            'overdue' => fn (Builder $q, $v) => $v ? $q->open()->whereDate('due_date', '<', today()) : $q,
            'source_type' => fn (Builder $q, $v) => $q->where('source_type', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('due_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('due_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'vendors' => Vendor::withTrashed()->whereHas('payables')->orderBy('business_name')->get(['id', 'business_name', 'trade_name'])
                ->mapWithKeys(fn ($v) => [$v->id => $v->display_name])->all(),
            'statuses' => Payable::getStatuses(),
            'origins' => Payable::getOrigins(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'vendor_id' => $options['vendors'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            'source_type' => $options['origins'][$value] ?? $value,
            'overdue' => 'Solo vencidas',
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(Payable::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.finance.payable-table', array_merge(
            [
                'payables' => $this->baseQuery()
                    ->orderByRaw("CASE WHEN status IN ('pending','partial') THEN 0 ELSE 1 END")
                    ->orderBy('due_date')->orderByDesc('id')
                    ->paginate($this->perPage),
                'pendingTotal' => (float) $this->baseQuery()->open()->sum('balance'),
            ],
            $this->filterOptions()
        ));
    }
}
