<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryStock;
use App\Http\Requests\Inventory\UpdateInventoryStockRequest;
use App\Services\Inventory\InventoryStockService\InventoryStockService;
use Illuminate\Support\Facades\Log;

class InventoryStockController extends Controller
{
    protected $stockService;

    public function __construct(InventoryStockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\InventoryStockTable.
     */
    public function index()
    {
        return view('inventory.stocks.index');
    }

    /**
     * Actualizar solo el Stock Mínimo
     */
    public function updateMinStock(UpdateInventoryStockRequest $request, InventoryStock $stock)
    {
        try {
            $this->stockService->updateMinStock($stock, $request->min_stock);

            // Cambiamos la respuesta JSON por un Redirect
            return redirect()
                ->route('inventory.stocks.index')
                ->with('success', "Stock mínimo de {$stock->product->name} en {$stock->warehouse->name} actualizado correctamente.");

        } catch (\Exception $e) {
            Log::error("Error actualizando min_stock: " . $e->getMessage());

            // Redirigir hacia atrás con el error para que el usuario sepa qué pasó
            return redirect()
                ->back()
                ->with('error', "No se pudo actualizar el stock mínimo.");
        }
    }
}
