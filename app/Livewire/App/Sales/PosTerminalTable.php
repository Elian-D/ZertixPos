<?php

namespace App\Livewire\App\Sales;

use App\Livewire\Base\DataTable;
use App\Models\Sales\Pos\PosTerminal;
use Illuminate\Database\Eloquent\Builder;

class PosTerminalTable extends DataTable
{
    public array $filters = [
        'search'  => '',
        'trashed' => '',
    ];

    protected function columns(): array
    {
        return array_filter([
            'id'                  => ['label' => 'ID Terminal'],
            'name'                => ['label' => 'Nombre Terminal', 'default' => true, 'mobile' => true],
            'warehouse_id'        => ['label' => 'Almacén Asociado', 'default' => true],
            'cash_account_id'     => module_enabled('accounting.advanced')
                ? ['label' => 'Cuenta Caja', 'default' => true]
                : null,
            'default_ncf_type_id' => ['label' => 'Tipo NCF Defecto'],
            'default_client_id'   => ['label' => 'Cliente Defecto'],
            'is_mobile'           => ['label' => 'Es Móvil'],
            'printer_format'      => ['label' => 'Formato Impresión', 'default' => true, 'mobile' => true],
            'is_active'           => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_at'          => ['label' => 'Fecha Creación', 'default' => true],
            'updated_at'          => ['label' => 'Fecha Actualización'],
        ]);
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where('name', 'like', "%{$v}%"),
        ];
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? PosTerminal::onlyTrashed()
            : PosTerminal::query();

        return $this->applyFilters($query->with(['warehouse', 'cashAccount', 'defaultNcfType', 'defaultClient']));
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('edit pos terminals'), 403);

        $terminal = PosTerminal::onlyTrashed()->findOrFail($id);
        $terminal->restore();

        $this->notify('success', "Terminal \"{$terminal->name}\" restaurada correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('delete pos terminals'), 403);

        $terminal = PosTerminal::onlyTrashed()->findOrFail($id);
        $name = $terminal->name;
        $terminal->forceDelete();

        $this->notify('success', "Terminal \"{$name}\" eliminada definitivamente.");
    }

    public function render()
    {
        $terminals = $this->baseQuery()->latest()->paginate($this->perPage);

        return view('livewire.app.sales.pos-terminal-table', [
            'terminals' => $terminals,
        ]);
    }
}
