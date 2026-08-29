<?php

use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Products\CategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\UnitController;
use Illuminate\Support\Facades\Route;

// Inventario (control de existencias — warehouses/stocks/movements) es núcleo
// flexible (REQ-10.4/10.8) — encendido por defecto, pero un negocio 100%
// servicios puede apagarlo desde "Funcionalidades del Sistema". Con el flag
// apagado, ese sub-grupo devuelve 404 (mismo criterio que un satélite).
//
// Fix (2026-08-28, hallado al mapear permisos↔módulo para REQ-2.7): el
// `module:inventory.tracking` envolvía TODO el archivo, incluyendo Productos/
// Categorías/Unidades — que son módulo base FIJO (docs/analisis/modulos-base-
// satelite.md §2.1: "Sin catálogo no hay qué vender", nunca opt-out). Con el
// bug, apagar Inventario borraba también el catálogo vendible completo. Se
// separa: solo warehouses/stocks/movements quedan detrás del flag; Productos
// queda en su propio grupo sin gate de módulo, mismo prefijo/nombres de ruta
// de siempre (`inventory.products.*`) para no romper nada que ya apunte ahí.
Route::prefix('inventory')->as('inventory.')->group(function () {

    Route::middleware('module:inventory.tracking')->group(function () {

        Route::middleware('permission:warehouses.manage')->group(function () {

            // warehouses.eliminados/restaurar/borrarDefinitivo/estado reemplazadas por
            // el tab "Papelera" + WarehouseTable::restore()/forceDelete()/toggleActivo()
            // del mismo índice — ver App\Livewire\App\Inventory\WarehouseTable y
            // docs/analisis/politica-soft-deletes.md §6. Sin create/edit/show reales
            // (CRUD por modal) — solo index/store/update/destroy.
            Route::resource('warehouses', WarehouseController::class)
                ->parameters(['warehouses' => 'warehouse'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('warehouses');
        });

        Route::get('stocks/', [InventoryStockController::class, 'index'])
            ->middleware('permission:inventory_stocks.view')
            ->name('stocks.index');

        Route::patch('stocks/{stock}/min-stock', [InventoryStockController::class, 'updateMinStock'])
            ->middleware('permission:inventory_stocks.update')
            ->name('stocks.update-min-stock');

        // stocks.export reemplazada por InventoryStockTable::export() del mismo
        // índice (Excel::download() puede devolverse directo desde una acción
        // Livewire) — ver ARCHITECTURE.md §7.

        Route::middleware('auth')->group(function () {

            Route::get('movements', [InventoryMovementController::class, 'index'])
                ->middleware('permission:inventory_movements.view')
                ->name('movements.index');

            Route::post('movements', [InventoryMovementController::class, 'store'])
                ->middleware('permission:inventory_movements.create_adjustment')
                ->name('movements.store');

            // movements.export reemplazada por InventoryMovementTable::export() del
            // mismo índice (Excel::download() puede devolverse directo desde una
            // acción Livewire) — ver ARCHITECTURE.md §7.
        });

        // Dashboard Inventario movido a routes/app/reports.php como reports.inventory
        // (Fase 7.9, sidebar) — vivía bajo app/inventory/dashboard, mismo prefijo
        // que el resto de este grupo, así que el sidebar resaltaba "Inventario" Y
        // "Reportes" a la vez al visitarlo.
    });

    // routes/app/products.php (antes) — merge dentro de Inventario (REQ-3.5),
    // namespace inventory.products.*, contenido sin cambios. Núcleo fijo — nunca
    // detrás de `module:inventory.tracking` (ver nota arriba).
    Route::prefix('products')->as('products.')->group(function () {

        Route::middleware('permission:categories.manage')->group(function () {

            // categories.eliminados/restaurar/borrarDefinitivo/estado reemplazadas
            // por el tab "Papelera" + CategoryTable::restore()/forceDelete()/
            // toggleActivo() del mismo índice — ver
            // App\Livewire\App\Inventory\CategoryTable. Sin create/edit/show
            // reales (CRUD por modal) — solo index/store/update/destroy.
            Route::resource('categories', CategoryController::class)
                ->parameters(['categories' => 'category'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('categories');
        });

        Route::middleware('permission:units.manage')->group(function () {

            // units.eliminados/restaurar/borrarDefinitivo/estado reemplazadas por
            // el tab "Papelera" + UnitTable::restore()/forceDelete()/toggleActivo()
            // del mismo índice — ver App\Livewire\App\Inventory\UnitTable. Sin
            // create/edit/show reales (CRUD por modal) — solo index/store/update/destroy.
            Route::resource('units', UnitController::class)
                ->parameters(['units' => 'unit'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('units');
        });

        Route::group([], function () {

            Route::get('/', [ProductController::class, 'index'])
                ->middleware('permission:products.view')
                ->name('index');

            Route::get('/crear', [ProductController::class, 'create'])
                ->middleware('permission:products.create')
                ->name('create');

            Route::post('/', [ProductController::class, 'store'])
                ->middleware('permission:products.create')
                ->name('store');

            Route::get('/{product}/editar', [ProductController::class, 'edit'])
                ->middleware('permission:products.edit')
                ->name('edit');

            Route::put('/{product}', [ProductController::class, 'update'])
                ->middleware('permission:products.edit')
                ->name('update');

            Route::delete('/{product}', [ProductController::class, 'destroy'])
                ->middleware('permission:products.delete')
                ->name('destroy');

            // products.bulk/eliminados/restore/borrarDefinitivo reemplazadas por el
            // tab "Papelera" del mismo índice — sin selección masiva (decisión
            // explícita del usuario, aplica a todos los módulos migrados) — ver
            // App\Livewire\App\Inventory\ProductTable::restore()/forceDelete() y
            // docs/analisis/politica-soft-deletes.md §6.
        });
    });
});
