<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Services\Reports\BusinessMetrics;
use Illuminate\Http\Request;

/**
 * Reporte de Inventario (v1.5.0 REQ-6.3): cuánto vale la mercancía (existencia × costo, y a
 * precio de venta), por almacén y por categoría, qué está bajo el mínimo y qué no se mueve.
 * Sin período: es una foto de hoy. ?warehouse= lo limita a un almacén.
 */
class InventoryReportController extends Controller
{
    const DEAD_STOCK_DAYS = 60;

    public function __invoke(Request $request, BusinessMetrics $metrics)
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);
        $warehouseId = $warehouses->contains('id', $request->integer('warehouse')) ? $request->integer('warehouse') : null;

        $tracked = InventoryStock::query()
            ->whereHas('product', fn ($q) => $q->where('type', Product::TYPE_PRODUCT)->where('is_active', true))
            ->when($warehouseId, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->where('min_stock', '>', 0)
            ->count();

        return view('reports.inventory', [
            'warehouses' => $warehouses,
            'warehouseId' => $warehouseId,
            'value' => $metrics->inventoryValue($warehouseId),
            'byWarehouse' => $metrics->inventoryValueByWarehouse($warehouseId),
            'byCategory' => $metrics->inventoryValueByCategory($warehouseId),
            'lowStock' => $metrics->lowStock($warehouseId, limit: 15),
            'lowStockCount' => $metrics->lowStockCount($warehouseId),
            'trackedCount' => $tracked,
            'deadStock' => $metrics->deadStock(self::DEAD_STOCK_DAYS, $warehouseId),
            'deadDays' => self::DEAD_STOCK_DAYS,
        ]);
    }
}
