<?php

namespace App\Livewire\App\Clients;

use App\Exports\Clients\ClientsExport;
use App\Livewire\Base\DataTable;
use App\Models\Clients\Client;
use App\Models\Geo\Province;
use App\Services\Client\ClientCatalogService;
use App\Services\Client\ClientService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ClientTable extends DataTable
{
    public array $filters = [
        'search'     => '',
        'is_active'  => '',
        'state'      => '',
        'type'       => '',
        'tax_type'   => '',
        'from_date'  => '',
        'to_date'    => '',
        'has_debt'   => '',
        'over_limit' => '',
        // 'papelera' en vez de un módulo aparte (docs/analisis/politica-soft-deletes.md
        // §6) — reemplaza el scope global (onlyTrashed) en vez de agregar un where,
        // por eso NO tiene entrada en filterMap(): se resuelve en baseQuery().
        'trashed'    => '',
    ];

    protected function columns(): array
    {
        return [
            'id'                    => ['label' => 'ID', 'default' => true],
            'name'                  => ['label' => 'Nombre Cliente', 'default' => true, 'mobile' => true],
            'tax_identifier_types'  => ['label' => 'Tipo ID Fiscal'],
            'tax_id'                => ['label' => 'ID Fiscal', 'default' => true],
            'type'                  => ['label' => 'Tipo'],
            'balance'               => ['label' => 'Saldo Pendiente', 'default' => true, 'mobile' => true],
            'credit_limit'          => ['label' => 'Límite Crédito', 'default' => true],
            'email'                 => ['label' => 'Email'],
            'phone'                 => ['label' => 'Teléfono'],
            'state'                 => ['label' => 'Estado/Provincia'],
            'city'                  => ['label' => 'Ciudad'],
            'address'               => ['label' => 'Dirección'],
            'is_active'             => ['label' => 'Estado', 'default' => true],
            'created_at'            => ['label' => 'Fecha Creación'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('name', 'like', "%{$v}%")
                ->orWhere('tax_id', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")),
            'is_active' => fn (Builder $q, $v) => $q->where('is_active', filter_var($v, FILTER_VALIDATE_BOOLEAN)),
            'state'     => fn (Builder $q, $v) => $q->where('provincia_id', $v),
            'type'      => fn (Builder $q, $v) => $q->where('type', $v),
            'tax_type'  => fn (Builder $q, $v) => $q->where('tax_identifier_type', $v),
            'from_date' => fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v),
            'to_date'   => fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v),
            'has_debt'  => fn (Builder $q, $v) => match ($v) {
                'yes'   => $q->where('balance', '>', 0),
                'no'    => $q->where('balance', '<=', 0),
                default => $q,
            },
            'over_limit' => fn (Builder $q, $v) => $v === '1'
                ? $q->whereColumn('balance', '>', 'credit_limit')
                : $q,
        ];
    }

    protected function filterOptions(): array
    {
        return app(ClientCatalogService::class)->getForFilters();
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'state'      => Province::find($value)?->name ?? $value,
            'tax_type'   => collect($this->filterOptions()['taxIdentifierTypes'] ?? [])->firstWhere('value', $value)['label'] ?? $value,
            'type'       => $value === 'company' ? 'Empresa' : 'Individual',
            'is_active'  => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            'has_debt'   => $value === 'yes' ? 'Con deuda' : 'Sin deuda',
            'over_limit' => 'Límite excedido',
            default      => parent::formatFilterValue($key, $value),
        };
    }

    protected function bulkActions(): array
    {
        $states = app(ClientCatalogService::class)->getForFilters()['states'];

        return [
            [
                'key' => 'change_status', 'label' => 'Cambiar estado', 'type' => 'select',
                'icon' => 'heroicon-o-user-group',
                'options' => ['1' => 'Activo', '0' => 'Inactivo'],
            ],
            [
                'key' => 'change_geo_state', 'label' => 'Cambiar región', 'type' => 'select',
                'icon' => 'heroicon-o-map-pin',
                'options' => $states->pluck('name', 'id')->all(),
            ],
            [
                'key' => 'reset_credit', 'label' => 'Quitar crédito', 'icon' => 'heroicon-o-no-symbol',
            ],
            [
                'key' => 'delete', 'label' => 'Eliminar', 'icon' => 'heroicon-o-trash', 'variant' => 'error',
                'confirm' => true, 'confirmMessage' => '¿Eliminar los clientes seleccionados?',
            ],
        ];
    }

    protected function performBulkAction(string $action, array $ids, mixed $value = null): void
    {
        // La ruta clients.bulk vieja exigía 'clients edit' — al mover la acción
        // acá adentro del componente Livewire, ese gate ya no lo pone el
        // middleware de la ruta, así que se replica acá.
        abort_unless(auth()->user()->can('clients edit'), 403);

        $service = app(ClientService::class);
        $count = $service->performBulkAction($ids, $action, $value);
        $label = $service->getActionLabel($action);

        $this->notify('success', "Se han {$label} correctamente {$count} registros.");
    }

    protected function baseQuery(): Builder
    {
        // Papelera (docs/analisis/politica-soft-deletes.md §6): reemplaza el
        // scope global en vez de agregar un where — por eso vive acá y no en
        // filterMap(). onlyTrashed() ya excluye los no-eliminados por su cuenta.
        $query = $this->filters['trashed'] === 'only'
            ? Client::onlyTrashed()
            : Client::query();

        return $this->applyFilters($query->withIndexRelations());
    }

    protected function currentPageIds(): array
    {
        return $this->baseQuery()->paginate($this->perPage)->pluck('id')->all();
    }

    public function export()
    {
        return Excel::download(
            new ClientsExport($this->baseQuery()),
            'respaldo-clientes-'.now()->format('d-m-Y-h:ia').'.xlsx'
        );
    }

    public function restore(int $id): void
    {
        // Reemplaza la ruta clients.restore vieja (permission:clients restore)
        // — mismo permiso, ahora chequeado adentro del componente.
        abort_unless(auth()->user()->can('clients restore'), 403);

        $client = Client::onlyTrashed()->findOrFail($id);
        $client->restore();

        $this->notify('success', "Cliente \"{$client->name}\" restaurado correctamente.");
    }

    public function forceDelete(int $id): void
    {
        // Reemplaza la ruta clients.borrarDefinitivo vieja (permission:clients delete).
        abort_unless(auth()->user()->can('clients delete'), 403);

        $client = Client::onlyTrashed()->findOrFail($id);
        $name = $client->name;
        $client->forceDelete();

        $this->notify('success', "Cliente \"{$name}\" eliminado definitivamente.");
    }

    public function render()
    {
        $clients = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.clients.client-table', array_merge(
            ['clients' => $clients],
            $this->filterOptions()
        ));
    }
}
