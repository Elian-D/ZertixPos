<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Warehouse;

class MovementCatalogService
{
    /** Filtros del kardex. Sin catálogo de productos: ya no hay formulario manual (v1.5.0 REQ-1.5). */
    public function getForFilters(): array
    {
        return [
            'warehouses' => Warehouse::activos()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'types' => InventoryMovement::getTypes(),
        ];
    }
}
