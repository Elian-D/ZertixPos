<?php

use App\Http\Controllers\Clients\BusinessTypeController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Clients\EquipmentController;
use App\Http\Controllers\Clients\EquipmentTypeController;
use App\Http\Controllers\Clients\PointOfSaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('clients')->as('clients.')->group(function () {

    Route::middleware('module:sales.delivery_points')->group(function () {
        Route::middleware('permission:configure business types')->group(function () {

            Route::get('businessTypes/eliminados', [BusinessTypeController::class, 'eliminadas'])
                ->name('businessTypes.eliminados');

            Route::resource('businessTypes', BusinessTypeController::class)
                ->parameters(['businessTypes' => 'negocio'])
                ->names('businessTypes');

            Route::patch('businessTypes/{negocio}/estado', [BusinessTypeController::class, 'toggleEstado'])
                ->name('businessTypes.toggle');

            Route::patch('businessTypes/{id}/restaurar', [BusinessTypeController::class, 'restaurar'])
                ->name('businessTypes.restaurar');

            Route::delete('businessTypes/{id}/borrar', [BusinessTypeController::class, 'borrarDefinitivo'])
                ->name('businessTypes.borrarDefinitivo');
        });
    });

    Route::group([], function () {

        Route::get('/', [ClientController::class, 'index'])
            ->middleware('permission:clients index')
            ->name('index');

        Route::get('/crear', [ClientController::class, 'create'])
            ->middleware('permission:clients create')
            ->name('create');

        Route::post('/', [ClientController::class, 'store'])
            ->middleware('permission:clients create')
            ->name('store');

        Route::get('/{client}/editar', [ClientController::class, 'edit'])
            ->middleware('permission:clients edit')
            ->name('edit');

        Route::put('/{client}', [ClientController::class, 'update'])
            ->middleware('permission:clients edit')
            ->name('update');

        Route::post('/bulk-action', [ClientController::class, 'bulk'])
            ->middleware('permission:clients edit')
            ->name('bulk');

        Route::get('/export', [ClientController::class, 'export'])->name('export');
        Route::get('/import', [ClientController::class, 'showImportForm'])->name('import.view');
        Route::post('/import', [ClientController::class, 'import'])->name('import.process');
        Route::get('/import-template', [ClientController::class, 'downloadTemplate'])->name('template');

        Route::delete('/{client}', [ClientController::class, 'destroy'])
            ->middleware('permission:clients delete')
            ->name('destroy');

        Route::get('/eliminados', [ClientController::class, 'eliminadas'])
            ->middleware('permission:clients restore')
            ->name('eliminados');

        Route::patch('/{id}/restaurar', [ClientController::class, 'restaurar'])
            ->middleware('permission:clients restore')
            ->name('restore');

        Route::delete('/{id}/forzar-eliminacion', [ClientController::class, 'borrarDefinitivo'])
            ->middleware('permission:clients delete')
            ->name('borrarDefinitivo');
    });

    Route::middleware('module:clients.field_assets')->group(function () {
        Route::group(['as' => 'equipment.'], function () {

            Route::get('equipments/eliminados', [EquipmentController::class, 'eliminadas'])
                ->middleware('permission:equipment restore')
                ->name('eliminados');

            Route::get('equipments/export', [EquipmentController::class, 'export'])
                ->name('export');

            Route::post('equipments/bulk-action', [EquipmentController::class, 'bulk'])
                ->middleware('permission:equipment edit')
                ->name('bulk');

            Route::get('equipments/', [EquipmentController::class, 'index'])
                ->middleware('permission:equipment index')
                ->name('index');

            Route::get('equipments/create', [EquipmentController::class, 'create'])
                ->middleware('permission:equipment create')
                ->name('create');

            Route::post('equipments/store', [EquipmentController::class, 'store'])
                ->middleware('permission:equipment create')
                ->name('store');

            Route::get('equipments/{equipment}/editar', [EquipmentController::class, 'edit'])
                ->middleware('permission:equipment edit')
                ->name('edit');

            Route::put('equipments/{equipment}', [EquipmentController::class, 'update'])
                ->middleware('permission:equipment edit')
                ->name('update');

            Route::delete('equipments/{id}', [EquipmentController::class, 'destroy'])
                ->middleware('permission:equipment delete')
                ->name('destroy');

            Route::patch('equipments/{id}/restaurar', [EquipmentController::class, 'restaurar'])
                ->middleware('permission:equipment restore')
                ->name('restore');

            Route::delete('equipments/{id}/forzar-eliminacion', [EquipmentController::class, 'borrarDefinitivo'])
                ->middleware('permission:equipment delete')
                ->name('borrarDefinitivo');
        });

        Route::middleware('permission:configure equipment types')->group(function () {

            Route::get('equipmentTypes/eliminados', [EquipmentTypeController::class, 'eliminadas'])
                ->name('equipmentTypes.eliminados');

            Route::resource('equipmentTypes', EquipmentTypeController::class)
                ->parameters(['equipmentTypes' => 'equipo'])
                ->names('equipmentTypes');

            Route::patch('equipmentTypes/{equipo}/estado', [EquipmentTypeController::class, 'toggleEstado'])
                ->name('equipmentTypes.toggle');

            Route::patch('equipmentTypes/{id}/restaurar', [EquipmentTypeController::class, 'restaurar'])
                ->name('equipmentTypes.restaurar');

            Route::delete('equipmentTypes/{id}/borrar', [EquipmentTypeController::class, 'borrarDefinitivo'])
                ->name('equipmentTypes.borrarDefinitivo');
        });
    });

    // Rename clients.pos.*→clients.delivery_points.* (REQ-3.6) — PointOfSale (ubicación
    // física del cliente en su ruta) colisionaba de nombre con sales.pos.* (POS Terminal/caja).
    Route::middleware('module:sales.delivery_points')->group(function () {
        Route::group(['as' => 'delivery_points.'], function () {

            Route::get('delivery-points/eliminados', [PointOfSaleController::class, 'eliminadas'])
                ->middleware('permission:pos restore')
                ->name('eliminados');

            Route::get('delivery-points/export', [PointOfSaleController::class, 'export'])
                ->name('export');

            Route::post('delivery-points/bulk-action', [PointOfSaleController::class, 'bulk'])
                ->middleware('permission:pos edit')
                ->name('bulk');

            Route::get('delivery-points/', [PointOfSaleController::class, 'index'])
                ->middleware('permission:pos index')
                ->name('index');

            Route::get('delivery-points/create', [PointOfSaleController::class, 'create'])
                ->middleware('permission:pos create')
                ->name('create');

            Route::post('delivery-points/store', [PointOfSaleController::class, 'store'])
                ->middleware('permission:pos create')
                ->name('store');

            Route::get('delivery-points/{pos}/editar', [PointOfSaleController::class, 'edit'])
                ->middleware('permission:pos edit')
                ->name('edit');

            Route::put('delivery-points/{pos}', [PointOfSaleController::class, 'update'])
                ->middleware('permission:pos edit')
                ->name('update');

            Route::delete('delivery-points/{pos}', [PointOfSaleController::class, 'destroy'])
                ->middleware('permission:pos delete')
                ->name('destroy');

            Route::patch('delivery-points/{id}/restaurar', [PointOfSaleController::class, 'restaurar'])
                ->middleware('permission:pos restore')
                ->name('restore');

            Route::delete('delivery-points/{id}/forzar-eliminacion', [PointOfSaleController::class, 'borrarDefinitivo'])
                ->middleware('permission:pos delete')
                ->name('borrarDefinitivo');
        });
    });
});
