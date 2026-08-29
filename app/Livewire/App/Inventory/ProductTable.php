<?php

namespace App\Livewire\App\Inventory;

use App\Livewire\Base\DataTable;
use App\Models\Products\Product;
use App\Services\Products\ProductCatalogService;
use Illuminate\Database\Eloquent\Builder;

class ProductTable extends DataTable
{
    public array $filters = [
        'search'      => '',
        'category_id' => '',
        'unit_id'     => '',
        'is_active'   => '',
        'trashed'     => '',
    ];

    protected function columns(): array
    {
        return [
            'name'           => ['label' => 'Nombre', 'default' => true, 'mobile' => true],
            'image_path'     => ['label' => 'Imagen', 'default' => true],
            'category_id'    => ['label' => 'Categoría'],
            'description'    => ['label' => 'Descripción'],
            // Precio con impuesto incluido — lo que el cliente paga en caja, visible por
            // defecto (Fase 5, REQ-5.11). 'price' (neto) queda oculto por defecto.
            'price_with_tax' => ['label' => 'Precio', 'default' => true, 'mobile' => true],
            'price'          => ['label' => 'Precio Neto'],
            'cost'           => ['label' => 'Costo'],
            'unit_id'        => ['label' => 'Unidad de Medida'],
            'is_active'      => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'is_stockable'   => ['label' => 'Tipo', 'default' => true],
            'created_at'     => ['label' => 'Fecha Creación'],
            'updated_at'     => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->where(fn (Builder $qq) => $qq
                ->where('name', 'like', "%{$v}%")
                ->orWhere('sku', 'like', "%{$v}%")),
            'category_id' => fn (Builder $q, $v) => $q->where('category_id', $v),
            'unit_id'     => fn (Builder $q, $v) => $q->where('unit_id', $v),
            'is_active'   => fn (Builder $q, $v) => $q->where('is_active', (bool) $v),
        ];
    }

    protected function filterOptions(): array
    {
        return app(ProductCatalogService::class)->getForFilters();
    }

    protected function nonChipFilterKeys(): array
    {
        return ['trashed'];
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'category_id' => $options['categories']->firstWhere('id', $value)?->name ?? $value,
            'unit_id'     => $options['units']->firstWhere('id', $value)?->name ?? $value,
            'is_active'   => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Activos' : 'Inactivos',
            default       => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        $query = $this->filters['trashed'] === 'only'
            ? Product::onlyTrashed()
            : Product::query();

        return $this->applyFilters($query->withIndexRelations());
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()->can('products.restore'), 403);

        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        $this->notify('success', "Producto \"{$product->name}\" restaurado correctamente.");
    }

    public function forceDelete(int $id): void
    {
        abort_unless(auth()->user()->can('products.delete'), 403);

        $product = Product::onlyTrashed()->findOrFail($id);
        $name = $product->name;
        $product->forceDelete();

        $this->notify('success', "Producto \"{$name}\" eliminado definitivamente.");
    }

    public function render()
    {
        $products = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.inventory.product-table', array_merge(
            ['products' => $products],
            $this->filterOptions()
        ));
    }
}
