<?php

namespace App\Livewire\App\Inventory;

use App\Exports\Inventory\InventoryStockExport;
use App\Livewire\Base\DataTable;
use App\Models\Inventory\InventoryStock;
use App\Services\Inventory\InventoryStockService\InventoryStockCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class InventoryStockTable extends DataTable
{
    public array $filters = [
        'search'       => '',
        'warehouse_id' => '',
        'category_id'  => '',
        'status'       => '',
    ];

    protected function columns(): array
    {
        return [
            'product_id'   => ['label' => 'Producto', 'default' => true, 'mobile' => true],
            'warehouse_id' => ['label' => 'Almacén', 'default' => true, 'mobile' => true],
            'quantity'     => ['label' => 'Stock Actual', 'default' => true, 'mobile' => true],
            'min_stock'    => ['label' => 'Stock Mínimo'],
            'status'       => ['label' => 'Estado', 'default' => true, 'mobile' => true],
            'created_at'   => ['label' => 'Fecha Creación'],
            'updated_at'   => ['label' => 'Última Actualización'],
        ];
    }

    protected function filterMap(): array
    {
        return [
            'search' => fn (Builder $q, $v) => $q->whereHas('product', fn (Builder $p) => $p
                ->where('name', 'like', "%{$v}%")
                ->orWhere('sku', 'like', "%{$v}%")),
            'warehouse_id' => fn (Builder $q, $v) => $q->where('warehouse_id', $v),
            'category_id'  => fn (Builder $q, $v) => $q->whereHas('product', fn (Builder $p) => $p->where('category_id', $v)),
            'status'       => fn (Builder $q, $v) => match ($v) {
                // Stock por debajo del mínimo (pero mayor a 0)
                'low' => $q->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0),
                // Stock en cero o negativo
                'out' => $q->where('quantity', '<=', 0),
                // Stock saludable
                'ok'  => $q->whereColumn('quantity', '>', 'min_stock'),
                default => $q,
            },
        ];
    }

    protected function filterOptions(): array
    {
        return app(InventoryStockCatalogService::class)->getForFilters();
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        $options = $this->filterOptions();

        return match ($key) {
            'warehouse_id' => $options['warehouses']->firstWhere('id', $value)?->name ?? $value,
            'category_id'  => $options['categories']->firstWhere('id', $value)?->name ?? $value,
            // No usa $options['statuses'] (InventoryStockCatalogService): esa clave
            // vive como 'low_stock' ahí para el dashboard de inventario, pero el
            // filtro real (arriba, filterMap()) siempre usó 'low' — mismo criterio
            // que la vista AJAX vieja, que ya evitaba ese catálogo y hardcodeaba las
            // opciones directo en el <select>.
            'status' => match ($value) {
                'ok'    => 'Stock Suficiente',
                'low'   => 'Stock Bajo',
                'out'   => 'Agotado',
                default => $value,
            },
            default => parent::formatFilterValue($key, $value),
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->applyFilters(
            InventoryStock::query()->with(['product.category', 'product.unit', 'warehouse'])
        );
    }

    public function export()
    {
        abort_unless(auth()->user()->can('inventory_stocks.export'), 403);

        return Excel::download(
            new InventoryStockExport($this->baseQuery()),
            'inventario-actual-'.now()->format('d-m-Y-H-i').'.xlsx'
        );
    }

    public function render()
    {
        $stocks = $this->baseQuery()->paginate($this->perPage);

        return view('livewire.app.inventory.inventory-stock-table', array_merge(
            ['stocks' => $stocks],
            $this->filterOptions()
        ));
    }
}
