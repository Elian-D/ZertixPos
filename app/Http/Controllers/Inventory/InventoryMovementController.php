<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryMovementRequest;
use App\Services\Inventory\InventoryMovementService;

class InventoryMovementController extends Controller
{
    public function __construct(
        protected InventoryMovementService $service
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\InventoryMovementTable.
     */
    public function index()
    {
        return view('inventory.movements.index');
    }

    /**
     * Registro de ajustes manuales (Subir/Bajar stock)
     */
    public function store(StoreInventoryMovementRequest $request)
    {
        $movement = $this->service->register($request->validated());

        // Obtenemos la URL de donde vino el usuario
        $previousUrl = url()->previous();

        // Definimos la ruta del dashboard (ajusta el nombre si es distinto)
        $dashboardRoute = route('reports.inventory');

        // Si la URL anterior contiene la ruta del dashboard, redirigimos allá
        if (str_contains($previousUrl, $dashboardRoute)) {
            return redirect()
                ->route('reports.inventory')
                ->with('success', "Movimiento registrado: El stock de {$movement->product->name} ha sido actualizado.");
        }

        // Por defecto, redirigir al index de movimientos
        return redirect()
            ->route('inventory.movements.index')
            ->with('success', "Ajuste #{$movement->id} realizado con éxito.");
    }
}
