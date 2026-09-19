<?php

namespace App\Livewire\App\Finance;

use App\Livewire\Base\DataTable;
use App\Models\Sales\Ncf\NcfType;
use Illuminate\Database\Eloquent\Builder;

/**
 * REQ-7.1/7.2/7.3 — catálogo fijo sembrado por `NcfTypeSeeder` (mismo patrón
 * que `installation_modules`): solo lectura, sin create/edit. El único campo
 * mutable por tenant es `is_active` (REQ-7.2), vía toggleActivo() — no hay
 * `permission:` de ruta protegiéndolo (Livewire no pasa por el middleware de
 * la ruta HTTP), así que el chequeo se replica a mano acá, igual que
 * `routes/app/finance.php` (grupo `ncf.types.*`, `permission:ncf_types.manage`).
 *
 * Sin papelera: `ncf_types` no tiene `deleted_at` (no es Categoría A de
 * docs/analisis/politica-soft-deletes.md — nadie puede borrar un tipo de
 * comprobante, solo desactivarlo). Sin filtros ni buscador en el mockup de
 * Stitch ("Tipos de Comprobante Fiscal - ZertixPOS") — es un catálogo de
 * ~12 filas fijas, no hace falta filtrar. Se deja `search` sobre `name`
 * de todos modos (ver docs/ui/datatable-migration-checklist.md §8): el
 * toolbar de `x-data-table.base-table` siempre renderiza el buscador, dejarlo
 * sin `filters.search` lo deja visible pero roto en silencio.
 */
class NcfTypeTable extends DataTable
{
    public array $filters = [
        'search' => '',
    ];

    protected function columns(): array
    {
        return [
            'full_code' => ['label' => 'Código', 'default' => true, 'mobile' => true],
            'name' => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'is_electronic' => ['label' => 'Tipo', 'default' => true],
            'requires_rnc' => ['label' => 'Requiere RNC', 'default' => true],
            'is_active' => ['label' => 'Estado', 'default' => true, 'mobile' => true],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where('name', 'like', "%{$v}%"),
        ];
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(NcfType::query());
    }

    public function toggleActivo(int $id): void
    {
        abort_unless(auth()->user()->can('ncf_types.manage'), 403);

        $type = NcfType::findOrFail($id);
        $type->update(['is_active' => ! $type->is_active]);

        $this->notify('success', 'Tipo de comprobante "'.$type->name.'" '.($type->is_active ? 'activado' : 'desactivado').' correctamente.');
    }

    public function render()
    {
        $types = $this->baseQuery()->orderBy('prefix')->orderBy('code')->paginate($this->perPage);

        return view('livewire.app.finance.ncf-type-table', [
            'types' => $types,
        ]);
    }
}
