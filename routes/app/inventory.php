<?php

use App\Http\Controllers\Inventory\InventoryCountController;
use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\InventoryTransferController;
use App\Http\Controllers\Inventory\InventoryWasteController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Products\ProductController;
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
            // docs/analisis/politica-soft-deletes.md §6. Crear/editar siguen por modal
            // (sin create/edit); show es la vista de detalle (v1.4.0 REQ-3.10).
            Route::resource('warehouses', WarehouseController::class)
                ->parameters(['warehouses' => 'warehouse'])
                ->only(['index', 'show', 'store', 'update', 'destroy'])
                ->whereNumber('warehouse')
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

            // Ajuste manual (v1.5.0 REQ-1.5): vista propia en vez del modal libre de antes.
            Route::get('movements/adjustment', [InventoryMovementController::class, 'create'])
                ->middleware('permission:inventory_movements.create_adjustment')
                ->name('movements.create');

            Route::post('movements', [InventoryMovementController::class, 'store'])
                ->middleware('permission:inventory_movements.create_adjustment')
                ->name('movements.store');

            // movements.export reemplazada por InventoryMovementTable::export() del
            // mismo índice (Excel::download() puede devolverse directo desde una
            // acción Livewire) — ver ARCHITECTURE.md §7.

            // Toma física (v1.5.0 REQ-2.1). Crear es un modal del listado (4 campos).
            // `count` es un permiso aparte: el contador entra al listado (solo borradores)
            // y a la pantalla de conteo, sin ver la revisión ni las diferencias.
            Route::prefix('counts')->as('counts.')->controller(InventoryCountController::class)->group(function () {
                Route::get('/', 'index')->middleware('permission:inventory_counts.view|inventory_counts.count')->name('index');
                Route::post('/', 'store')->middleware('permission:inventory_counts.create')->name('store');
                Route::get('/{count}', 'show')->whereNumber('count')->middleware('permission:inventory_counts.view')->name('show');
                Route::get('/{count}/count', 'count')->whereNumber('count')->middleware('permission:inventory_counts.count')->name('count');
                Route::put('/{count}/count', 'save')->whereNumber('count')->middleware('permission:inventory_counts.count')->name('save');
                Route::post('/{count}/apply', 'apply')->whereNumber('count')->middleware('permission:inventory_counts.apply')->name('apply');
                Route::patch('/{count}/cancel', 'cancel')->whereNumber('count')->middleware('permission:inventory_counts.create')->name('cancel');
                Route::get('/{count}/pdf', 'pdf')->whereNumber('count')->middleware('permission:inventory_counts.view')->name('pdf');
            });

            // Mermas (v1.5.0 REQ-2.2). Se aplican al guardar; anular devuelve el stock.
            Route::prefix('wastes')->as('wastes.')->controller(InventoryWasteController::class)->group(function () {
                Route::get('/', 'index')->middleware('permission:inventory_wastes.view')->name('index');
                Route::get('/create', 'create')->middleware('permission:inventory_wastes.create')->name('create');
                Route::post('/', 'store')->middleware('permission:inventory_wastes.create')->name('store');
                Route::get('/{waste}', 'show')->whereNumber('waste')->middleware('permission:inventory_wastes.view')->name('show');
                Route::patch('/{waste}/void', 'void')->whereNumber('waste')->middleware('permission:inventory_wastes.void')->name('void');
                Route::get('/{waste}/pdf', 'pdf')->whereNumber('waste')->middleware('permission:inventory_wastes.view')->name('pdf');
            });

            // Transferencias (v1.5.0 REQ-2.4). Borrador → Enviada (en tránsito) → Recibida.
            Route::prefix('transfers')->as('transfers.')->controller(InventoryTransferController::class)->group(function () {
                Route::get('/', 'index')->middleware('permission:inventory_transfers.view')->name('index');
                Route::get('/create', 'create')->middleware('permission:inventory_transfers.create')->name('create');
                Route::post('/', 'store')->middleware('permission:inventory_transfers.create')->name('store');
                Route::get('/{transfer}', 'show')->whereNumber('transfer')->middleware('permission:inventory_transfers.view')->name('show');
                Route::get('/{transfer}/edit', 'edit')->whereNumber('transfer')->middleware('permission:inventory_transfers.create')->name('edit');
                Route::put('/{transfer}', 'update')->whereNumber('transfer')->middleware('permission:inventory_transfers.create')->name('update');
                Route::post('/{transfer}/send', 'send')->whereNumber('transfer')->middleware('permission:inventory_transfers.send')->name('send');
                Route::get('/{transfer}/receive', 'receiveForm')->whereNumber('transfer')->middleware('permission:inventory_transfers.receive')->name('receive.form');
                Route::post('/{transfer}/receive', 'receive')->whereNumber('transfer')->middleware('permission:inventory_transfers.receive')->name('receive');
                Route::patch('/{transfer}/cancel', 'cancel')->whereNumber('transfer')->middleware('permission:inventory_transfers.create')->name('cancel');
                Route::get('/{transfer}/pdf', 'pdf')->whereNumber('transfer')->middleware('permission:inventory_transfers.view')->name('pdf');
            });
        });

        // Dashboard Inventario movido a routes/app/reports.php como reports.inventory
        // (Fase 7.9, sidebar) — vivía bajo app/inventory/dashboard, mismo prefijo
        // que el resto de este grupo, así que el sidebar resaltaba "Inventario" Y
        // "Reportes" a la vez al visitarlo.
    });

    // routes/app/products.php (antes) — merge dentro de Inventario (REQ-3.5),
    // namespace inventory.products.*, contenido sin cambios. Núcleo fijo — nunca
    // detrás de `module:inventory.tracking` (ver nota arriba).
    // REQ-7.4 (2026-09-14) — categories/units se mudaron a
    // routes/app/configuration.php (configuration.categories.*/
    // .units.*, prefijo config/catalogs/...) junto con el resto de
    // "Catálogos del Sistema". Sin cambios de controlador/permiso, solo de
    // dónde vive la ruta.
    Route::prefix('products')->as('products.')->group(function () {

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

            Route::get('/{product}', [ProductController::class, 'show'])
                ->whereNumber('product')
                ->middleware('permission:products.view')
                ->name('show');

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
