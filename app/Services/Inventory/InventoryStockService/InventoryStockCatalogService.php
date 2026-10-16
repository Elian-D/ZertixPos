<?php

namespace App\Services\Inventory\InventoryStockService;

use App\Models\Inventory\Warehouse;
use App\Models\Products\Category;

class InventoryStockCatalogService
{
    public function getForFilters(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'categoryFilter' => Category::filterOptions(),
            'statuses'   => [
                'ok'        => 'Stock Suficiente',
                'low_stock' => 'Stock Bajo',
                'out'       => 'Agotado'
            ]
        ];
    }
}