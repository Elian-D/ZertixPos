<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\Payable;
use App\Models\Accounting\PayablePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Pagos a proveedores (v1.5.0 REQ-4.3, EGR). Se anulan desde la CxP; aquí se consultan y
 * se imprime el recibo.
 */
class PayablePaymentTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'account_id' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'payment_date' => ['label' => 'Fecha', 'default' => true],
            'vendor' => ['label' => 'Proveedor', 'default' => true, 'mobile' => true],
            'payable' => ['label' => 'Cuenta por pagar', 'default' => true],
            'account' => ['label' => 'Desde', 'default' => true],
            'reference' => ['label' => 'Referencia'],
            'amount' => ['label' => 'Monto', 'default' => true, 'mobile' => true],
            'status' => ['label' => 'Estado', 'default' => true],
            'creator' => ['label' => 'Registró'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['número', 'proveedor', 'referencia'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhere('reference', 'like', "%{$v}%")
                ->orWhereHas('payable.vendor', fn (Builder $w) => $w->where('business_name', 'like', "%{$v}%")->orWhere('trade_name', 'like', "%{$v}%"))),
            'account_id' => fn (Builder $q, $v) => $q->where('financial_account_id', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('payment_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('payment_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'accounts' => FinancialAccount::orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => PayablePayment::getStatuses(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'account_id' => $options['accounts'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(PayablePayment::query()->withIndexRelations());
    }

    public function render()
    {
        return view('livewire.app.finance.payable-payment-table', array_merge(
            [
                'payments' => $this->baseQuery()->orderByDesc('payment_date')->orderByDesc('id')->paginate($this->perPage),
                'paidTotal' => (float) $this->baseQuery()->where('status', PayablePayment::STATUS_ACTIVE)->sum('amount'),
                // Modal "Pagar deuda": las deudas abiertas, la más próxima a vencer primero.
                'openPayables' => auth()->user()->can('payables.pay')
                    ? Payable::open()->with('vendor:id,business_name,trade_name')->orderBy('due_date')->orderBy('id')
                        ->get(['id', 'number', 'vendor_id', 'concept', 'balance', 'due_date', 'status'])
                    : collect(),
            ],
            $this->filterOptions()
        ));
    }
}
