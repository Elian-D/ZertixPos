<?php

use App\Http\Controllers\Clients\PosQuickCustomerController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\Ncf\NcfDashboardController;
use App\Http\Controllers\Sales\Ncf\NcfLogController;
use App\Http\Controllers\Sales\Ncf\NcfSequenceController;
use App\Http\Controllers\Sales\Ncf\NcfTypeController;
use App\Http\Controllers\Sales\Ncf\RncLookupController;
use App\Http\Controllers\Sales\Pos\PosCheckoutController;
use App\Http\Controllers\Sales\Pos\PosConfigController;
use App\Http\Controllers\Sales\Pos\PosSessionController;
use App\Http\Controllers\Sales\Pos\PosTerminalController;
use App\Http\Controllers\Sales\Pos\PosTerminalLockController;
use App\Http\Controllers\Sales\QuoteController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SalesDashboardController;
// Importante
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->as('sales.')->group(function () {

    // routes/admin/sales/sales.php
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

    // routes/admin/sales/invoices.php
    Route::middleware('auth')->group(function () {

        // Exportación de historial
        Route::get('invoice/export', [InvoiceController::class, 'export'])
            ->middleware('permission:export invoices')
            ->name('invoices.export');

        Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview')
            ->middleware(['auth', 'permission:view invoices']);

        // Listado principal con AJAX
        Route::get('invoice/', [InvoiceController::class, 'index'])
            ->middleware('permission:view invoices')
            ->name('invoices.index');

        // Visualización de detalle (El documento legal)
        Route::get('invoice/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('permission:view invoices')
            ->name('invoices.show');

        // Impresión (Generación de PDF/Ticket)
        Route::get('invoice/{invoice}/print', [InvoiceController::class, 'print'])
            ->middleware('permission:print invoices')
            ->name('invoices.print');
    });

    // routes/admin/sales/ncf.php
    Route::middleware(['auth', 'permission:manage ncf sequences'])->group(function () {

        // En lugar de un middleware Closure, validamos antes de definir el grupo
        // Si la configuración fiscal está apagada, estas rutas ni siquiera se registran
        // o puedes envolverlas en una condición:

        if (module_enabled('sales.ncf')) {

            Route::prefix('ncf')->name('ncf.')->group(function () {

                // Dashboard y otras rutas
                Route::get('/dashboard', function () { /* tu controller */
                })->name('dashboard');

                Route::prefix('sequences')->name('sequences.')->group(function () {
                    Route::get('/', [NcfSequenceController::class, 'index'])->name('index');
                    Route::post('/', [NcfSequenceController::class, 'store'])->name('store');
                    Route::delete('/{sequence}', [NcfSequenceController::class, 'destroy'])->name('destroy');
                    Route::patch('/{sequence}/threshold', [NcfSequenceController::class, 'updateThreshold'])->name('update-threshold');
                    Route::patch('/{sequence}/extend', [NcfSequenceController::class, 'extend'])->name('extend');
                });

                Route::group(['prefix' => 'logs', 'as' => 'logs.'], function () {
                    Route::get('/', [NcfLogController::class, 'index'])->name('index');
                    Route::get('/export/excel', [NcfLogController::class, 'exportExcel'])->name('export.excel');
                    Route::get('/export/txt', [NcfLogController::class, 'exportTxt'])->name('export.txt');
                });

                Route::group(['prefix' => 'types', 'as' => 'types.'], function () {
                    Route::get('/', [NcfTypeController::class, 'index'])->name('index');
                    Route::post('/', [NcfTypeController::class, 'store'])->name('store');
                    Route::put('/{ncfType}', [NcfTypeController::class, 'update'])->name('update');
                });
            });
        } else {
            // Opcional: Ruta fallback si intentan entrar y está desactivado
            Route::any('ncf/{any?}', function () {
                return redirect('/admin/config')->with('error', 'La gestión fiscal está desactivada en la configuración general.');
            })->where('any', '.*');
        }
    });

    // routes/admin/sales/pos.php
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

    // routes/admin/sales/quotes.php
    // Cotizaciones es núcleo flexible (REQ-10.4/10.8) — encendido por defecto, pero un
    // negocio de venta directa que nunca cotiza puede apagarlo. Con el flag apagado,
    // todo el grupo devuelve 404 — incluye approve/cancel/convert sobre cotizaciones
    // ya existentes, no solo la creación de nuevas (mismo criterio "se congela" de
    // REQ-10.5: si el módulo está pausado, tampoco se debería seguir operando sobre
    // lo que ya existe).
    Route::middleware(['auth', 'module:sales.quotes'])->prefix('quotes')->name('quotes.')->group(function () {

        // Listado principal (DataTables / AJAX)
        Route::get('/', [QuoteController::class, 'index'])
            ->middleware('permission:view quotes')
            ->name('index');

        // Creación (Livewire Builder)
        Route::get('/create', [QuoteController::class, 'create'])
            ->middleware('permission:create quotes')
            ->name('create');

        Route::post('/', [QuoteController::class, 'store'])
            ->middleware('permission:create quotes')
            ->name('store');

        // Preview (Vista previa en iframe)
        Route::get('/{quote}/preview', [QuoteController::class, 'preview'])
            ->middleware('permission:view quotes')
            ->name('preview');

        // Detalle (Vista previa antes de imprimir o convertir)
        Route::get('/{quote}', [QuoteController::class, 'show'])
            ->middleware('permission:view quotes')
            ->name('show');

        // Edición (Solo para estado Borrador)
        Route::get('/{quote}/edit', [QuoteController::class, 'edit'])
            ->middleware('permission:edit quotes')
            ->name('edit');

        Route::put('/{quote}', [QuoteController::class, 'update'])
            ->middleware('permission:edit quotes')
            ->name('update');

        // --- ACCIONES DE NEGOCIO (Cambios de estado) ---

        // Marcar como aprobada (lista para el cliente)
        Route::patch('/{quote}/approve', [QuoteController::class, 'approve'])
            ->middleware('permission:convert quotes')
            ->name('approve');

        // Cancelar (por error o decisión del cliente)
        Route::patch('/{quote}/cancel', [QuoteController::class, 'cancel'])
            ->middleware('permission:cancel quotes')
            ->name('cancel');

        // Convertir en venta final
        Route::post('/{quote}/convert', [QuoteController::class, 'convert'])
            ->middleware('permission:convert quotes')
            ->name('convert');

        // --- IMPRESIÓN ---

        Route::get('/{quote}/print/{format?}', [QuoteController::class, 'print'])
            ->middleware('permission:view quotes')
            ->name('print');
    });

    Route::get('/dashboard', SalesDashboardController::class)->name('dashboard');
    Route::get('/ncf/dashboard', NcfDashboardController::class)->name('ncf.dashboard');
});
