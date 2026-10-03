<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryMovementRequest;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Services\Inventory\InventoryMovementService;
use Exception;

/**
 * Kardex (v1.5.0 REQ-1.5). El stock cambia por documentos con nombre; la única
 * escritura a mano es el ajuste — vista propia, permiso propio, motivo y comentario.
 */
class InventoryMovementController extends Controller
{
    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\InventoryMovementTable.
     */
    public function index()
    {
        return view('inventory.movements.index');
    }

    public function create()
    {
        return view('inventory.movements.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('id')->get(['id', 'name']),
            'products' => Product::where('type', Product::TYPE_PRODUCT)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'sku', 'barcode']),
            // Existencia por almacén y producto, para mostrar "actual → queda" en cada línea.
            'stocks' => InventoryStock::get(['warehouse_id', 'product_id', 'quantity'])
                ->groupBy('warehouse_id')
                ->map(fn ($rows) => $rows->mapWithKeys(fn ($s) => [$s->product_id => (float) $s->quantity])),
            'reasons' => InventoryMovement::getAdjustmentReasons(),
        ]);
    }

    public function store(StoreInventoryMovementRequest $request, InventoryMovementService $service)
    {
        try {
            $movements = $service->registerAdjustment(
                (int) $request->warehouse_id,
                $request->reason,
                $request->notes,
                $request->validated('lines'),
            );
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        $count = $movements->count();

        return redirect()->route('inventory.movements.index')
            ->with('success', "Ajuste aplicado: {$count} ".($count === 1 ? 'movimiento registrado.' : 'movimientos registrados.'));
    }
}
