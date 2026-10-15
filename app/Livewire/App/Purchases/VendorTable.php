<?php

namespace App\Livewire\App\Purchases;

use App\Livewire\Base\DataTable;
use App\Models\Purchases\Vendor;
use App\Services\Purchases\VendorService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado de proveedores (v1.5.0 REQ-4.1), con la papelera como pestaña
 * (docs/analisis/politica-soft-deletes.md §6): restaurar y borrar definitivo viven aquí.
 */
class VendorTable extends DataTable
{
    public array $filters = [
        'search' => '',
        'payment_condition' => '',
        'is_active' => '',
        'trashed' => '',
    ];

    protected function columns(): array
    {
        return [
            'business_name' => ['label' => 'Proveedor', 'default' => true, 'mobile' => true],
            'tax_id' => ['label' => 'RNC / Cédula', 'default' => true],
            'contact' => ['label' => 'Contacto', 'default' => true],
            'condition' => ['label' => 'Condición', 'default' => true],
            'pending' => ['label' => 'Saldo pendiente', 'default' => true, 'mobile' => true],
            'rating' => ['label' => 'Calificación', 'default' => true],
            'is_active' => ['label' => 'Estado', 'default' => true],
            'created_at' => ['label' => 'Creado'],
        ];
    }

    /** En qué busca el filtro 'search' de abajo — debe coincidir con su closure. */
    protected function searchFields(): array
    {
        return ['razón social', 'nombre comercial', 'RNC'];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('business_name', 'like', "%{$v}%")
                ->orWhere('trade_name', 'like', "%{$v}%")
                ->orWhere('tax_id', 'like', '%'.preg_replace('/\D/', '', $v).'%')),
            'payment_condition' => fn (Builder $q, $v) => $q->where('payment_condition', $v),
            'is_active' => fn (Builder $q, $v) => $q->where('is_active', (bool) $v),
        ];
    }

    protected function filterOptions(): array
    {
        return [
            'conditions' => Vendor::getConditions(),
            'statuses' => ['1' => 'Activos', '0' => 'Inactivos'],
        ];
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'payment_condition' => Vendor::getConditions()[$value] ?? $value,
            'is_active' => $value ? 'Activos' : 'Inactivos',
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only' ? Vendor::onlyTrashed() : Vendor::query();

        return $this->applyFilters($query->withIndexRelations());
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('vendors.restore'), 403);

        $vendor = Vendor::onlyTrashed()->findOrFail($id);
        $vendor->restore();

        $this->notify('success', "Proveedor \"{$vendor->display_name}\" restaurado.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('vendors.delete'), 403);

        $vendor = Vendor::onlyTrashed()->findOrFail($id);

        try {
            app(VendorService::class)->forceDelete($vendor);
        } catch (DomainException $e) {
            $this->notify('error', $e->getMessage());

            return;
        }

        $this->notify('success', "Proveedor \"{$vendor->display_name}\" eliminado definitivamente.");
    }

    public function render()
    {
        return view('livewire.app.purchases.vendor-table', array_merge(
            ['vendors' => $this->baseQuery()->orderBy('business_name')->paginate($this->perPage)],
            $this->filterOptions()
        ));
    }
}
