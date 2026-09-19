<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreWarehouseRequest;
use App\Http\Requests\Inventory\UpdateWarehouseRequest;
use App\Models\Inventory\Warehouse;
use App\Services\Inventory\WarehouseService\WarehouseService;
use App\Traits\SoftDeletesTrait;
use Exception;

class WarehouseController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected WarehouseService $service
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\WarehouseTable.
     */
    public function index()
    {
        return view('inventory.warehouses.index');
    }

    public function store(StoreWarehouseRequest $request)
    {
        try {
            $warehouse = $this->service->store($request->validated());

            return redirect()->route('inventory.warehouses.index')
                ->with('success', "Almacén \"{$warehouse->name}\" creado con éxito.".
                    ($warehouse->accountingAccount ? " Vinculado a la cuenta: {$warehouse->accountingAccount->code}." : ''));
        } catch (Exception $e) {
            return back()->with('error', 'Error al crear el almacén: '.$e->getMessage())->withInput();
        }
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        try {
            $this->service->update($warehouse, $request->validated());

            return redirect()->route('inventory.warehouses.index')
                ->with('success', "Almacén \"{$warehouse->name}\" actualizado correctamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        // Validación preventiva: No borrar si tiene cuentas con saldo (opcional aquí, ideal en el service)
        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'No se puede eliminar un almacén que aún tiene existencia de productos.');
        }

        return $this->destroyTrait($warehouse);
    }

    /* Configuración del Trait para destroy() (eliminados/restaurar/borrarDefinitivo
     * del trait ya no se usan — reemplazados por el tab "Papelera" + WarehouseTable
     * ::restore()/forceDelete(); toggleEstado() reemplazado por WarehouseTable
     * ::toggleActivo(), ver docs/analisis/politica-soft-deletes.md §6). */
    protected function getModelClass(): string
    {
        return Warehouse::class;
    }

    protected function getViewFolder(): string
    {
        return 'inventory.warehouses';
    }

    protected function getRouteIndex(): string
    {
        return 'inventory.warehouses.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'inventory.warehouses.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Almacén';
    }
}
