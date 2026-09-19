<?php

namespace App\Livewire\Admin;

use App\Livewire\Base\DataTable;
use App\Models\Configuration\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado de tenants del Panel de Súper Admin (REQ-5.1) — motor Livewire
 * estándar (ARCHITECTURE.md), no la tabla legacy AJAX. Guard `landlord`
 * (ver App\Http\Controllers\Admin\TenantController), solo lectura: sin
 * crear/editar/borrar tenant desde acá — REQ-5.2 (plan de solo lectura) y
 * REQ-5.6 (crear tenant reusa el wizard público, ver docs/features/v1.3.0.md
 * §4.3/§5.6), no un formulario propio.
 */
class TenantsTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'plan_id' => '',
        'status' => '',
    ];

    protected function columns(): array
    {
        return [
            'business_name' => ['label' => 'Negocio', 'default' => true, 'mobile' => true],
            'domain' => ['label' => 'Subdominio', 'default' => true, 'mobile' => true],
            'plan' => ['label' => 'Plan', 'default' => true],
            'status' => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_at' => ['label' => 'Creado', 'default' => true],
        ];
    }

    protected function filterMap(): array
    {
        return [
            // Busca por negocio o por subdominio (REQ-5.1, ajuste 2026-09-14
            // pedido por el usuario) — el subdominio vive en `domains`, una
            // relación aparte, no una columna de `tenants`.
            'search' => fn (Builder $q, $v) => $q->where(function (Builder $q2) use ($v) {
                $q2->where('business_name', 'like', "%{$v}%")
                    ->orWhereHas('domains', fn (Builder $dq) => $dq->where('domain', 'like', "%{$v}%"));
            }),
            'plan_id' => fn (Builder $q, $v) => $q->where('plan_id', $v),
            'status' => fn (Builder $q, $v) => $q->whereHas('latestSubscription', fn (Builder $sq) => $sq->where('status', $v)),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'plans' => Plan::orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => self::STATUS_LABELS,
        ];
    }

    /** Estado real de facturación (Subscription::status, REQ-3.1) — no un estado administrativo aparte (REQ-3.7). */
    public const STATUS_LABELS = [
        'trialing' => 'En prueba',
        'active' => 'Activo',
        'past_due' => 'Vencido',
        'cancelled' => 'Cancelado',
    ];

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'plan_id' => $options['plans'][$value] ?? $value,
            'status' => $options['statuses'][$value] ?? $value,
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(Tenant::query()->withIndexRelations());
    }

    public function render()
    {
        $tenants = $this->baseQuery()->latest('created_at')->paginate($this->perPage);

        return view('livewire.admin.tenants-table', array_merge(
            ['tenants' => $tenants],
            $this->filterOptions()
        ));
    }
}
