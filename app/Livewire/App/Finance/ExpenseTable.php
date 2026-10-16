<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Accounting\Expense;
use App\Models\Purchases\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Gastos (v1.5.0 REQ-4.4): filtra por categoría, condición, estado, proveedor y fecha, y
 * muestra arriba lo gastado de lo filtrado (base del estado de resultados de la Fase 6).
 */
class ExpenseTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'category' => '',
        'payment_condition' => '',
        'status' => '',
        'vendor_id' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'expense_date' => ['label' => 'Fecha', 'default' => true],
            'concept' => ['label' => 'Concepto', 'default' => true, 'mobile' => true],
            'category' => ['label' => 'Categoría', 'default' => true],
            'vendor' => ['label' => 'Proveedor', 'default' => true],
            'condition' => ['label' => 'Pago', 'default' => true],
            'amount' => ['label' => 'Monto', 'default' => true, 'mobile' => true],
            'status' => ['label' => 'Estado', 'default' => true],
            'supplier_invoice' => ['label' => 'Factura o NCF'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'concepto', 'proveedor', 'factura'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhere('concept', 'like', "%{$v}%")
                ->orWhere('supplier_invoice', 'like', "%{$v}%")
                ->orWhereHas('vendor', fn (Builder $w) => $w->where('business_name', 'like', "%{$v}%")->orWhere('trade_name', 'like', "%{$v}%"))),
            'category' => fn (Builder $q, $v) => $q->where('category', $v),
            'payment_condition' => fn (Builder $q, $v) => $q->where('payment_condition', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'vendor_id' => fn (Builder $q, $v) => $q->where('vendor_id', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('expense_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('expense_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'categories' => Expense::getCategories(),
            'conditions' => Expense::getConditions(),
            'statuses' => Expense::getStatuses(),
            'vendors' => Vendor::withTrashed()->whereHas('expenses')->orderBy('business_name')->get(['id', 'business_name', 'trade_name'])
                ->mapWithKeys(fn ($v) => [$v->id => $v->display_name])->all(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'category' => $options['categories'][$value] ?? $value,
            'payment_condition' => $options['conditions'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            'vendor_id' => $options['vendors'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(Expense::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.finance.expense-table', array_merge(
            [
                'expenses' => $this->baseQuery()->orderByDesc('expense_date')->orderByDesc('id')->paginate($this->perPage),
                // Lo gastado de lo filtrado (solo registrados: uno anulado no cuenta).
                'spentTotal' => (float) $this->baseQuery()->where('status', Expense::STATUS_APPLIED)->sum('amount'),
            ],
            $this->filterOptions()
        ));
    }
}
