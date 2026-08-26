<?php

namespace App\Filters\Inventory;

use App\Filters\Base\QueryFilter;

class InventoryMovementFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'       => MovementSearchFilter::class,
            'warehouse_id' => MovementWarehouseFilter::class,
            'type'         => MovementTypeFilter::class,
            'from_date'    => MovementDateFromFilter::class,
            'to_date'      => MovementDateToFilter::class,
        ];
    }
}