<?php

use App\Http\Controllers\Inventory\InventoryDashboardController;
use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->as('inventory.')->group(function () {

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

    Route::get('/dashboard', InventoryDashboardController::class)
        ->middleware('permission:view inventory dashboard')
        ->name('dashboard.index');
});
