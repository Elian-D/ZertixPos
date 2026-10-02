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

    /** Productos listados en el show del almacén (el resto, en Stock Actual). */
    private const LIST_LIMIT = 50;

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

    /**
     * Detalle del almacén (v1.4.0 Fase 3, patrón Infolist — /filament-show).
     * Reemplaza el modal "view-warehouse" del listado.
     */
    public function show(Warehouse $warehouse)
    {
        $warehouse->load('accountingAccount:id,code,name');

        $stocks = $warehouse->stocks()
            ->with(['product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'cost', 'type', 'unit_id')->with('unit:id,abbreviation')])
            ->orderByDesc('quantity')
            ->limit(self::LIST_LIMIT)
            ->get();

        // Resumen en una sola consulta agregada (no se calcula sobre la lista limitada).
        $summary = $warehouse->stocks()
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->selectRaw('
                COUNT(*) as items,
                SUM(CASE WHEN inventory_stocks.quantity > 0 THEN 1 ELSE 0 END) as with_stock,
                SUM(CASE WHEN inventory_stocks.quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN inventory_stocks.quantity > 0 AND inventory_stocks.min_stock > 0 AND inventory_stocks.quantity <= inventory_stocks.min_stock THEN 1 ELSE 0 END) as low_stock,
                COALESCE(SUM(inventory_stocks.quantity), 0) as units,
                COALESCE(SUM(CASE WHEN inventory_stocks.quantity > 0 THEN inventory_stocks.quantity * products.cost ELSE 0 END), 0) as value
            ')
            ->first();

        return view('inventory.warehouses.show', [
            'warehouse' => $warehouse,
            'stocks' => $stocks,
            'summary' => $summary,
            'listLimit' => self::LIST_LIMIT,
            'types' => Warehouse::getTypes(),
        ]);
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

            // back(): el modal de editar vive tanto en el listado como en el show.
            return back()->with('success', "Almacén \"{$warehouse->name}\" actualizado correctamente.");
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
