<?php

namespace App\Livewire\App\Sales;

use App\Livewire\Base\DataTable;
use App\Models\Sales\Pos\PosSession;
use App\Services\Sales\Pos\PosSessionServices\PosSessionCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PosSessionTable extends DataTable
{
    public array $filters = [
        'search'            => '',
        'terminal_id'       => '',
        'user_id'           => '',
        'opened_by_user_id' => '',
        'closed_by_user_id' => '',
        'status'            => '',
        'difference_reason' => '',
        'from_date'         => '',
        'to_date'           => '',
    ];

    protected function columns(): array
    {
        return [
            'id'                => ['label' => 'ID Turno'],
            'terminal_id'       => ['label' => 'Terminal/Caja', 'default' => true, 'mobile' => true],
            'opened_by_user_id' => ['label' => 'Abierto Por', 'default' => true, 'mobile' => true],
            'closed_by_user_id' => ['label' => 'Cerrado Por', 'default' => true],
            'status'            => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'opened_at'         => ['label' => 'Fecha Apertura', 'default' => true],
            'closed_at'         => ['label' => 'Fecha Cierre'],
            'opening_balance'   => ['label' => 'Balance Inicial', 'default' => true, 'mobile' => true],
            'closing_balance'   => ['label' => 'Balance Final (Arqueo)', 'default' => true],
            'expected_balance'  => ['label' => 'Balance Esperado'],
            'difference'        => ['label' => 'Diferencia'],
            'notes'             => ['label' => 'Notas/Observaciones'],
            'created_at'        => ['label' => 'Fecha Registro'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search'            => fn (Builder $q, $v) => $q->where('notes', 'like', "%{$v}%"),
            'terminal_id'       => fn (Builder $q, $v) => $q->where('terminal_id', $v),
            'user_id'           => fn (Builder $q, $v) => $q->where('user_id', $v),
            'opened_by_user_id' => fn (Builder $q, $v) => $q->where('opened_by_user_id', $v),
            'closed_by_user_id' => fn (Builder $q, $v) => $q->where('closed_by_user_id', $v),
            'status'            => fn (Builder $q, $v) => $q->where('status', $v),
            'difference_reason' => fn (Builder $q, $v) => $q->where('difference_reason', $v),
            'from_date'         => fn (Builder $q, $v) => $q->where('opened_at', '>=', Carbon::parse($v)->startOfMinute()),
            'to_date'           => fn (Builder $q, $v) => $q->where('opened_at', '<=', Carbon::parse($v)->endOfMinute()),
        ];
    }

    protected function filterOptions(): array
    {
        return app(PosSessionCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'terminal_id'                         => $options['terminals']->firstWhere('id', $value)?->name ?? $value,
            'user_id', 'opened_by_user_id', 'closed_by_user_id' => $options['users']->firstWhere('id', $value)?->name ?? $value,
            'status'                               => $options['statuses'][$value] ?? $value,
            'difference_reason'                    => $options['difference_reasons'][$value] ?? $value,
            default                                 => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(
            PosSession::query()->with(['terminal', 'user', 'openedBy', 'closedBy'])
        );
    }

    public function render()
    {
        $sessions = $this->baseQuery()->orderBy('opened_at', 'desc')->paginate($this->perPage);

        return view('livewire.app.sales.pos-session-table', array_merge(
            [
                'sessions'            => $sessions,
                'available_terminals' => app(PosSessionCatalogService::class)->getForForm()['available_terminals'],
            ],
            $this->filterOptions()
        ));
    }
}
