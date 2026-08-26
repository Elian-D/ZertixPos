<?php

use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Products\CategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\UnitController;
use Illuminate\Support\Facades\Route;

// Inventario es núcleo flexible (REQ-10.4/10.8) — encendido por defecto, pero un
// negocio 100% servicios puede apagarlo desde "Funcionalidades del Sistema". Con el
// flag apagado, todo este grupo devuelve 404 (mismo criterio que un satélite).
Route::middleware('module:inventory.tracking')->prefix('inventory')->as('inventory.')->group(function () {

    Route::middleware('permission:configure warehouses')->group(function () {

        Route::get('warehouses/eliminados', [WarehouseController::class, 'eliminadas'])
            ->name('warehouses.eliminados');

        Route::resource('warehouses', WarehouseController::class)
            ->parameters(['warehouses' => 'warehouse'])
            ->names('warehouses');

        Route::patch('warehouses/{warehouse}/estado', [WarehouseController::class, 'toggleEstado'])
            ->name('warehouses.toggle');

        Route::patch('warehouses/{id}/restaurar', [WarehouseController::class, 'restaurar'])
            ->name('warehouses.restaurar');

        Route::delete('warehouses/{id}/borrar', [WarehouseController::class, 'borrarDefinitivo'])
            ->name('warehouses.borrarDefinitivo');
    });

    Route::get('stocks/', [InventoryStockController::class, 'index'])
        ->middleware('permission:inventory stocks index')
        ->name('stocks.index');

    Route::patch('stocks/{stock}/min-stock', [InventoryStockController::class, 'updateMinStock'])
        ->middleware('permission:inventory stocks update')
        ->name('stocks.update-min-stock');

    Route::get('stocks/export', [InventoryStockController::class, 'export'])
        ->middleware('permission:inventory stocks export')
        ->name('stocks.export');

    Route::middleware('auth')->group(function () {

        Route::get('movements', [InventoryMovementController::class, 'index'])
            ->middleware('permission:view inventory movements')
            ->name('movements.index');

        Route::post('movements', [InventoryMovementController::class, 'store'])
            ->middleware('permission:create inventory adjustments')
            ->name('movements.store');

        Route::get('movements/export', [InventoryMovementController::class, 'export'])
            ->middleware('permission:view inventory movements')
            ->name('movements.export');
    });

    // Dashboard Inventario movido a routes/app/reports.php como reports.inventory
    // (Fase 7.9, sidebar) — vivía bajo app/inventory/dashboard, mismo prefijo
    // que el resto de este grupo, así que el sidebar resaltaba "Inventario" Y
    // "Reportes" a la vez al visitarlo.

    // routes/app/products.php (antes) — merge dentro de Inventario (REQ-3.5),
    // namespace inventory.products.*, contenido sin cambios.
    Route::prefix('products')->as('products.')->group(function () {

        Route::middleware('permission:configure categories')->group(function () {

            Route::get('categories/eliminados', [CategoryController::class, 'eliminadas'])
                ->name('categories.eliminados');

            Route::resource('categories', CategoryController::class)
                ->parameters(['categories' => 'category'])
                ->names('categories');

            Route::patch('categories/{category}/estado', [CategoryController::class, 'toggleEstado'])
                ->name('categories.toggle');

            Route::patch('categories/{id}/restaurar', [CategoryController::class, 'restaurar'])
                ->name('categories.restaurar');

            Route::delete('categories/{id}/borrar', [CategoryController::class, 'borrarDefinitivo'])
                ->name('categories.borrarDefinitivo');
        });

        Route::middleware('permission:configure units')->group(function () {

            Route::get('units/eliminados', [UnitController::class, 'eliminadas'])
                ->name('units.eliminados');

            Route::resource('units', UnitController::class)
                ->parameters(['units' => 'unit'])
                ->names('units');

            Route::patch('units/{unit}/estado', [UnitController::class, 'toggleEstado'])
                ->name('units.toggle');

            Route::patch('units/{id}/restaurar', [UnitController::class, 'restaurar'])
                ->name('units.restaurar');

            Route::delete('units/{id}/borrar', [UnitController::class, 'borrarDefinitivo'])
                ->name('units.borrarDefinitivo');
        });

        Route::group([], function () {

            Route::get('/', [ProductController::class, 'index'])
                ->middleware('permission:view products')
                ->name('index');

            Route::get('/crear', [ProductController::class, 'create'])
                ->middleware('permission:create products')
                ->name('create');

            Route::post('/', [ProductController::class, 'store'])
                ->middleware('permission:create products')
                ->name('store');

            Route::get('/{product}/editar', [ProductController::class, 'edit'])
                ->middleware('permission:edit products')
                ->name('edit');

            Route::put('/{product}', [ProductController::class, 'update'])
                ->middleware('permission:edit products')
                ->name('update');

            Route::post('/bulk-action', [ProductController::class, 'bulk'])
                ->middleware('permission:edit products')
                ->name('bulk');

            Route::delete('/{product}', [ProductController::class, 'destroy'])
                ->middleware('permission:delete products')
                ->name('destroy');

            Route::get('/eliminados', [ProductController::class, 'eliminadas'])
                ->middleware('permission:restore products')
                ->name('eliminados');

            Route::patch('/{id}/restaurar', [ProductController::class, 'restaurar'])
                ->middleware('permission:restore products')
                ->name('restore');

            Route::delete('/{id}/forzar-eliminacion', [ProductController::class, 'borrarDefinitivo'])
                ->middleware('permission:delete products')
                ->name('borrarDefinitivo');
        });
    });
});
