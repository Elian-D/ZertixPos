<?php

namespace App\Http\Controllers\Inventory;

use App\Filters\Warehouses\WarehousesFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreWarehouseRequest;
use App\Http\Requests\Inventory\UpdateWarehouseRequest;
use App\Models\Inventory\Warehouse;
use App\Services\Inventory\WarehouseService\WarehouseCatalogService;
use App\Services\Inventory\WarehouseService\WarehouseService;
use App\Tables\WarehouseTable;
use App\Traits\SoftDeletesTrait;
use Exception;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected WarehouseService $service,
        protected WarehouseCatalogService $catalogService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', WarehouseTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // Cargamos la relación contable para la tabla
        $warehouses = (new WarehousesFilters($request))
            ->apply(Warehouse::query()->with('accountingAccount'))
            ->paginate($perPage)
            ->withQueryString();

        $viewData = array_merge([
            'warehouses' => $warehouses,
            'visibleColumns' => $visibleColumns,
            'allColumns' => WarehouseTable::allColumns(),
            'defaultDesktop' => WarehouseTable::defaultDesktop(),
            'defaultMobile' => WarehouseTable::defaultMobile(),
        ], $this->catalogService->getForIndex());

        if ($request->ajax()) {
            return view('inventory.warehouses.partials.table', $viewData)->render();
        }

        return view('inventory.warehouses.index', $viewData);
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

    public function toggleEstado(Warehouse $warehouse)
    {
        try {
            $this->service->toggle($warehouse);

            return back()->with('success', 'Estado del almacén actualizado con éxito.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
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
