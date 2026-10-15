<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Movimientos de Tesorería (v1.5.0 REQ-3.5): el kardex de todas las cuentas, mismo
 * patrón que Movimientos de Stock. El más reciente primero; el saldo de cada fila es el
 * que quedó en su cuenta al registrarlo. Cada fila abre su detalle, desde donde se anula
 * o se corrige un movimiento manual.
 */
class FinancialMovementTable extends DataTable
{
    /** Cuenta inicial del filtro ("Ver todos" desde el show); la asigna Livewire. */
    public ?int $accountId = null;

    public array $filters = [
        'search' => '',
        'account_id' => '',
        'origin' => '',
        'direction' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    public function mount(): void
    {
        parent::mount();

        if ($this->accountId) {
            $this->filters['account_id'] = (string) $this->accountId;
        }
    }

    protected function columns(): array
    {
        return [
            'movement_date' => ['label' => 'Fecha', 'default' => true, 'mobile' => true],
            'account' => ['label' => 'Cuenta', 'default' => true, 'mobile' => true],
            'origin' => ['label' => 'Origen', 'default' => true],
            'detail' => ['label' => 'Detalle', 'default' => true],
            'in' => ['label' => 'Entra', 'default' => true],
            'out' => ['label' => 'Sale', 'default' => true],
            'signed' => ['label' => 'Monto', 'mobile' => true],
            'balance_after' => ['label' => 'Saldo', 'default' => true],
            'creator' => ['label' => 'Registró'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['nota'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where('notes', 'like', "%{$v}%"),
            'account_id' => fn (Builder $q, $v) => $q->where('financial_account_id', $v),
            'origin' => fn (Builder $q, $v) => $q->where('origin', $v),
            'direction' => fn (Builder $q, $v) => $q->where('direction', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('movement_date', '>=', Carbon::parse($v)),
            'to_date' => fn (Builder $q, $v) => $q->whereDate('movement_date', '<=', Carbon::parse($v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'accounts' => FinancialAccount::orderByDesc('is_active')->orderBy('name')->pluck('name', 'id')->all(),
            'origins' => FinancialMovement::getOrigins(),
            'directions' => [FinancialMovement::DIRECTION_IN => 'Entradas', FinancialMovement::DIRECTION_OUT => 'Salidas'],
        ];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'account_id' => $options['accounts'][$value] ?? $value,
            'origin' => $options['origins'][$value] ?? $value,
            'direction' => $options['directions'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(FinancialMovement::query()->withIndexRelations());
    }

    public function render()
    {
        // Las peticiones de Livewire no pasan por el middleware de la ruta.
        abort_unless(auth()->user()->can('financial_accounts.view'), 403);

        return view('livewire.app.finance.financial-movement-table', array_merge(
            ['movements' => $this->baseQuery()->orderByDesc('id')->paginate($this->perPage)],
            $this->filterOptions()
        ));
    }
}
