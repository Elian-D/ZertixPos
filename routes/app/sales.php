<?php

use App\Http\Controllers\Clients\PosQuickCustomerController;
use App\Http\Controllers\Sales\Ncf\RncLookupController;
use App\Http\Controllers\Sales\Pos\PosCheckoutController;
use App\Http\Controllers\Sales\Pos\PosConfigController;
use App\Http\Controllers\Sales\Pos\PosSessionController;
use App\Http\Controllers\Sales\Pos\PosTerminalController;
use App\Http\Controllers\Sales\Pos\PosTerminalLockController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SalesDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->as('sales.')->group(function () {

    // Venta core (sales.*) — Facturas (finance.invoices.*) y NCF (finance.ncf.*)
    // viven en routes/app/finance.php, Cotizaciones en routes/app/quotes.php (REQ-3.3).
    Route::middleware('auth')->group(function () {
        // Listado y Dashboard de Ventas
        Route::get('/', [SaleController::class, 'index'])
            ->middleware('permission:view sales')
            ->name('index');

        // Creación de Ventas (Formulario y Proceso)
        Route::get('/create', [SaleController::class, 'create'])
            ->middleware('permission:create sales')
            ->name('create');

        Route::post('/', [SaleController::class, 'store'])
            ->middleware('permission:create sales')
            ->name('store');

        // Acción de Anulación (Genera reversión contable e inventario)
        Route::patch('/{sale}/cancel', [SaleController::class, 'cancel'])
            ->middleware('permission:cancel sales')
            ->name('cancel');

        // Exportación de Reportes
        Route::get('/export', [SaleController::class, 'export'])
            ->middleware('permission:view sales')
            ->name('export');

        Route::get('sales/{sale}/print-invoice', [SaleController::class, 'printInvoice'])
            ->name('print-invoice')
            ->middleware('permission:print invoices');
    });

    // routes/app/sales/pos.php
    Route::prefix('pos')->name('pos.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Configuración Global
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings')
            ->name('settings.')
            ->controller(PosConfigController::class)
            ->group(function () {

                Route::get('/', 'edit')
                    ->name('edit')
                    ->middleware('permission:pos config view');

                Route::put('/', 'update')
                    ->name('update')
                    ->middleware('permission:pos config update');
            });

        /*
        |--------------------------------------------------------------------------
        | Terminales
        |--------------------------------------------------------------------------
        */
        Route::prefix('terminals')
            ->name('terminals.')
            ->controller(PosTerminalController::class)
            ->group(function () {

                Route::get('/', 'index')->name('index');

                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::get('/{pos_terminal}/edit', 'edit')->name('edit');
                Route::put('/{pos_terminal}', 'update')->name('update');

                Route::delete('/{pos_terminal}', 'destroy')->name('destroy');

                // Soft Deletes
                Route::get('/eliminados', 'eliminadas')->name('eliminadas');
                Route::post('/{id}/restore', 'restaurar')->name('restore');
                Route::delete('/{id}/force-delete', 'borrarDefinitivo')->name('force-delete');
            });

        /*
        |--------------------------------------------------------------------------
        | Sesiones de Caja
        |--------------------------------------------------------------------------
        */
        Route::prefix('sessions')
            ->name('sessions.')
            ->controller(PosSessionController::class)
            ->group(function () {

                Route::get('/', 'index')->name('index');
                Route::get('/{pos_session}', 'show')->name('show');
                Route::get('/{pos_session}/print', 'print')->name('print');

                // Apertura
                Route::post('/open', 'store')->name('store');

                // Cierre — vista dedicada (Fase 9.3), gateada por permiso en la ruta
                // (403 nativo, mismo patrón que Lobby/Workspace/Checkout en 9.0).
                Route::get('/{pos_session}/close', 'closeForm')
                    ->name('close-form')
                    ->middleware('permission:pos sessions manage');
                Route::patch('/{pos_session}/close', 'close')->name('close');

                // Edición administrativa
                Route::put('/{pos_session}', 'update')->name('update');
            });

        /*
        |--------------------------------------------------------------------------
        | Bloqueo de Terminal
        |--------------------------------------------------------------------------
        | El Lobby (`sales.pos.index`) es el único punto donde se pide el PIN — no
        | existe una pantalla de "lock" dedicada. Bloquear solo invalida la sesión
        | de la terminal y regresa al Lobby; reentrar exige el PIN de nuevo vía
        | CheckTerminalAccess + PosTerminalLobby.
        */
        Route::controller(PosTerminalLockController::class)->group(function () {

            Route::get('/terminal/{pos_terminal}/lock', 'lock')
                ->name('lock');

            // Usado por los modales de apertura/cierre de sesión del backoffice (fuera del Lobby).
            Route::post('/verify-pin', 'verify')
                ->name('verify-pin');
            // ->middleware('throttle:pos-pin');

            Route::post('/heartbeat', 'heartbeat')
                ->name('heartbeat');
        });

        /*
        |--------------------------------------------------------------------------
        | Movimientos de Caja — DESHABILITADO (Fase 9.1, docs/features/POS-Interfaz.md)
        |--------------------------------------------------------------------------
        | Ocultado a propósito: hoy exige accounting_account_id (acoplamiento a
        | Contabilidad, ver docs/analisis/sobre-ingenieria-modulos.md §1) y permite
        | salidas de efectivo genéricas que no reflejan la operación real. Se
        | reintroducirá simplificado (sin cuenta contable, razones curadas) más
        | adelante. El cierre de sesión no depende de estas rutas.
        */
        // Route::prefix('cash-movements')
        //     ->name('cash-movements.')
        //     ->controller(PosCashMovementController::class)
        //     ->group(function () {

        //         Route::get('/', 'index')->name('index');
        //         Route::post('/', 'store')->name('store');
        //     });

        /*
        |--------------------------------------------------------------------------
        | Cliente Rápido
        |--------------------------------------------------------------------------
        */
        Route::post(
            '/quick-customer',
            [PosQuickCustomerController::class, 'store']
        )->name('quick-customer.store');

        /*
        |--------------------------------------------------------------------------
        | Consulta de RNC (DGII) para Crédito Fiscal — proxy server-side
        |--------------------------------------------------------------------------
        */
        Route::get('/rnc-lookup', [RncLookupController::class, 'lookup'])
            ->name('rnc-lookup')
            ->middleware(['auth', 'throttle:20,1']);

        /*
        |--------------------------------------------------------------------------
        | FLUJO DE ENTRADA POS (Fase 7.0 & 7.1)
        |--------------------------------------------------------------------------
        */
        // 7.0 — Lobby de Selección de Terminales y Apertura de Sesión
        Route::get('/lobby', \App\Livewire\Sales\Pos\Pages\PosTerminalLobby::class)
            ->name('index')
            ->middleware(['auth', 'verified', 'permission:pos sessions manage']);

        // 7.1 — POS Workspace (Mesa de Trabajo Completa del POS)
        Route::get('/workspace/{pos_terminal}', \App\Livewire\Sales\Pos\Pages\PosWorkspace::class)
            ->name('workspace')
            ->middleware(['auth', 'verified', 'permission:pos sessions manage', 'check.terminal.access']);

        // 7.4 — Checkout Engine: registra la venta originada en el Workspace
        Route::post('/workspace/{pos_terminal}/checkout', [PosCheckoutController::class, 'store'])
            ->name('checkout.store')
            ->middleware(['auth', 'verified', 'permission:pos sessions manage', 'check.terminal.access']);
    });

    Route::get('/dashboard', SalesDashboardController::class)->name('dashboard');
});
