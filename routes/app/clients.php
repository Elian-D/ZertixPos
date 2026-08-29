<?php

use App\Http\Controllers\Clients\BusinessTypeController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Clients\EquipmentController;
use App\Http\Controllers\Clients\EquipmentTypeController;
use App\Http\Controllers\Clients\PointOfSaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('clients')->as('clients.')->group(function () {

    Route::middleware('module:sales.delivery_points')->group(function () {
        Route::middleware('permission:business_types.manage')->group(function () {

            // businessTypes.eliminados/restaurar/borrarDefinitivo/estado
            // reemplazadas por el tab "Papelera" + toggleActivo() del mismo
            // índice — ver App\Livewire\App\Clients\BusinessTypeTable.

            Route::resource('businessTypes', BusinessTypeController::class)
                ->parameters(['businessTypes' => 'negocio'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('businessTypes');
        });
    });

    Route::group([], function () {

        Route::get('/', [ClientController::class, 'index'])
            ->middleware('permission:clients.view')
            ->name('index');

        Route::get('/crear', [ClientController::class, 'create'])
            ->middleware('permission:clients.create')
            ->name('create');

        Route::post('/', [ClientController::class, 'store'])
            ->middleware('permission:clients.create')
            ->name('store');

        Route::get('/{client}/editar', [ClientController::class, 'edit'])
            ->middleware('permission:clients.edit')
            ->name('edit');

        Route::put('/{client}', [ClientController::class, 'update'])
            ->middleware('permission:clients.edit')
            ->name('update');

        Route::get('/import', [ClientController::class, 'showImportForm'])->name('import.view');
        Route::post('/import', [ClientController::class, 'import'])->name('import.process');
        Route::get('/import-template', [ClientController::class, 'downloadTemplate'])->name('template');

        Route::delete('/{client}', [ClientController::class, 'destroy'])
            ->middleware('permission:clients.delete')
            ->name('destroy');

        // clients.eliminados/restore/borrarDefinitivo (vista de papelera aparte)
        // reemplazadas por el tab "Papelera" del mismo índice — ver
        // App\Livewire\App\Clients\ClientTable::restore()/forceDelete() y
        // docs/analisis/politica-soft-deletes.md §6.
    });

    Route::middleware('module:clients.field_assets')->group(function () {
        Route::group(['as' => 'equipment.'], function () {

            // equipment.eliminados/restore/borrarDefinitivo/bulk/export
            // reemplazadas por el tab "Papelera" + botón Exportar del mismo
            // índice — ver App\Livewire\App\Clients\EquipmentTable.

            Route::get('equipments/', [EquipmentController::class, 'index'])
                ->middleware('permission:equipment.view')
                ->name('index');

            Route::get('equipments/create', [EquipmentController::class, 'create'])
                ->middleware('permission:equipment.create')
                ->name('create');

            Route::post('equipments/store', [EquipmentController::class, 'store'])
                ->middleware('permission:equipment.create')
                ->name('store');

            Route::get('equipments/{equipment}/editar', [EquipmentController::class, 'edit'])
                ->middleware('permission:equipment.edit')
                ->name('edit');

            Route::put('equipments/{equipment}', [EquipmentController::class, 'update'])
                ->middleware('permission:equipment.edit')
                ->name('update');

            Route::delete('equipments/{id}', [EquipmentController::class, 'destroy'])
                ->middleware('permission:equipment.delete')
                ->name('destroy');
        });

        Route::middleware('permission:equipment_types.manage')->group(function () {

            // equipmentTypes.eliminados/restaurar/borrarDefinitivo/estado
            // reemplazadas por el tab "Papelera" + toggleActivo() del mismo
            // índice — ver App\Livewire\App\Clients\EquipmentTypeTable.

            Route::resource('equipmentTypes', EquipmentTypeController::class)
                ->parameters(['equipmentTypes' => 'equipo'])
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('equipmentTypes');
        });
    });

    // Rename clients.pos.*→clients.delivery_points.* (REQ-3.6) — PointOfSale (ubicación
    // física del cliente en su ruta) colisionaba de nombre con sales.pos.* (POS Terminal/caja).
    Route::middleware('module:sales.delivery_points')->group(function () {
        Route::group(['as' => 'delivery_points.'], function () {

            // delivery_points.eliminados/restore/borrarDefinitivo/bulk/export
            // (vista de papelera aparte, bulk actions, export por form GET)
            // reemplazadas por el tab "Papelera" + botón Exportar del mismo
            // índice — ver App\Livewire\App\Clients\PointOfSaleTable.

            Route::get('delivery-points/', [PointOfSaleController::class, 'index'])
                ->middleware('permission:delivery_points.view')
                ->name('index');

            Route::get('delivery-points/create', [PointOfSaleController::class, 'create'])
                ->middleware('permission:delivery_points.create')
                ->name('create');

            Route::post('delivery-points/store', [PointOfSaleController::class, 'store'])
                ->middleware('permission:delivery_points.create')
                ->name('store');

            Route::get('delivery-points/{pos}/editar', [PointOfSaleController::class, 'edit'])
                ->middleware('permission:delivery_points.edit')
                ->name('edit');

            Route::put('delivery-points/{pos}', [PointOfSaleController::class, 'update'])
                ->middleware('permission:delivery_points.edit')
                ->name('update');

            Route::delete('delivery-points/{pos}', [PointOfSaleController::class, 'destroy'])
                ->middleware('permission:delivery_points.delete')
                ->name('destroy');
        });
    });
});
