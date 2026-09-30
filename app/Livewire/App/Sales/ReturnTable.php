<?php

namespace App\Livewire\App\Sales;

use App\Livewire\Base\DataTable;
use App\Models\Sales\Returns\SaleReturn;
use App\Models\User;
use App\Services\Sales\Returns\ReturnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Listado de Devoluciones (v1.4.0 REQ-2.6). Crear vive en el modal de Ventas
 * (ReturnForm); aquí solo se consulta y se anula.
 */
class ReturnTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'refund_method' => '',
        'reason' => '',
        'user_id' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    protected function columns(): array
    {
        return [
            'number' => ['label' => 'Número', 'default' => true, 'mobile' => true],
            'sale_id' => ['label' => 'Venta origen', 'default' => true],
            'client' => ['label' => 'Cliente', 'default' => true, 'mobile' => true],
            'user_id' => ['label' => 'Usuario', 'default' => true],
            'reason' => ['label' => 'Motivo', 'default' => true],
            'refund_method' => ['label' => 'Método', 'default' => true],
            'refund_value' => ['label' => 'Monto', 'default' => true, 'mobile' => true],
            'status' => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_at' => ['label' => 'Fecha', 'default' => true],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('number', 'like', "%{$v}%")
                ->orWhereHas('sale', fn (Builder $sq) => $sq->where('number', 'like', "%{$v}%")
                    ->orWhereHas('client', fn (Builder $cq) => $cq->where('name', 'like', "%{$v}%")))),
            'refund_method' => fn (Builder $q, $v) => $q->where('refund_method', $v),
            'reason' => fn (Builder $q, $v) => $q->where('reason', $v),
            'user_id' => fn (Builder $q, $v) => $q->where('user_id', $v),
            'status' => fn (Builder $q, $v) => $q->where('status', $v),
            'from_date' => fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfMinute()),
            'to_date' => fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfMinute()),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'refund_methods' => SaleReturn::getRefundMethods(),
            'reasons' => SaleReturn::getReasons(),
            'statuses' => SaleReturn::getStatuses(),
            'users' => User::whereIn('id', SaleReturn::select('user_id'))->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'refund_method' => $options['refund_methods'][$value] ?? $value,
            'reason' => $options['reasons'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            'user_id' => $options['users'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(SaleReturn::query()->withIndexRelations());
    }

    public function void(int $id, ReturnService $service): void
    {
        abort_unless(auth()->user()->can('returns.void'), 403);

        $return = SaleReturn::findOrFail($id);

        try {
            $service->void($return);
            $this->notify('success', "Devolución {$return->number} anulada.");
        } catch (Throwable $e) {
            $this->notify('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.app.sales.return-table', array_merge(
            ['returns' => $this->baseQuery()->latest()->paginate($this->perPage)],
            $this->filterOptions()
        ));
    }
}
