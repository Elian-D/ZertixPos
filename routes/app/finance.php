<?php

use App\Http\Controllers\Accounting\AccountingAccountController;
use App\Http\Controllers\Accounting\CollectionController;
use App\Http\Controllers\Accounting\FinancialOverviewController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\ReceivableController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\Ncf\NcfLogController;
use App\Http\Controllers\Sales\Ncf\NcfSequenceController;
use App\Http\Controllers\Sales\Ncf\NcfTypeController;
use Illuminate\Support\Facades\Route;

// Reemplaza accounting.php (REQ-3.4) — nombres de ruta accounting.*→finance.*,
// absorbe Facturas (antes sales.invoices.*→finance.invoices.*) y NCF (antes
// sales.ncf.*→finance.ncf.*), que dejan de vivir dentro de "sales".
Route::prefix('finance')->as('finance.')->group(function () {

    // Plan de Cuentas y Asientos — contabilidad formal, satélite (REQ-03.5). Nunca envuelve
    // receivables/collections: CxC y su abono operativo son base, ver config/modules.php.
    Route::middleware('module:accounting.advanced')->group(function () {

        Route::middleware('permission:configure accounting account')->group(function () {

            Route::get('accounts/eliminados', [AccountingAccountController::class, 'eliminadas'])
                ->name('accounts.eliminados');

            Route::resource('accounts', AccountingAccountController::class)
                ->parameters(['accounts' => 'accounting_account'])
                ->names('accounts');

            Route::patch('accounts/{id}/restaurar', [AccountingAccountController::class, 'restaurar'])
                ->name('accounts.restaurar');

            Route::delete('accounts/{id}/borrar', [AccountingAccountController::class, 'borrarDefinitivo'])
                ->name('accounts.borrarDefinitivo');
        });

        Route::middleware('auth')->group(function () {

            Route::get('journal_entries', [JournalEntryController::class, 'index'])
                ->middleware('permission:view journal entries')
                ->name('journal_entries.index');

            Route::get('journal_entries/create', [JournalEntryController::class, 'create'])
                ->middleware('permission:create journal entries')
                ->name('journal_entries.create');

            Route::post('journal_entries', [JournalEntryController::class, 'store'])
                ->middleware('permission:create journal entries')
                ->name('journal_entries.store');

            Route::get('journal_entries/{journal_entry}/edit', [JournalEntryController::class, 'edit'])
                ->middleware('permission:edit journal entries')
                ->name('journal_entries.edit');

            Route::put('journal_entries/{journal_entry}', [JournalEntryController::class, 'update'])
                ->middleware('permission:edit journal entries')
                ->name('journal_entries.update');

            Route::patch('journal_entries/{journal_entry}/post', [JournalEntryController::class, 'post'])
                ->middleware('permission:post journal entries')
                ->name('journal_entries.post');

            Route::patch('journal_entries/{journal_entry}/cancel', [JournalEntryController::class, 'cancel'])
                ->middleware('permission:cancel journal entries')
                ->name('journal_entries.cancel');

            Route::get('journal_entries/export', [JournalEntryController::class, 'export'])
                ->middleware('permission:view journal entries')
                ->name('journal_entries.export');
        });
    });

    // document_types vive en routes/app/configuration.php (REQ-10.3) — es un
    // catálogo del sistema completo, no algo específico de Finanzas.

    // CxC es núcleo flexible (REQ-10.4/10.8) — encendido por defecto, pero un negocio
    // 100% contado puede apagarlo. Con el flag apagado, todo el grupo devuelve 404.
    Route::middleware(['auth', 'module:sales.receivables'])->group(function () {

        Route::prefix('receivables')->name('receivables.')->group(function () {

            Route::get('/', [ReceivableController::class, 'index'])
                ->middleware('permission:view receivables')
                ->name('index');

            Route::get('/eliminados', [ReceivableController::class, 'eliminadas'])
                ->name('eliminados');

            Route::delete('/{receivable}', [ReceivableController::class, 'destroy'])
                ->middleware('permission:cancel receivables')
                ->name('destroy');

            Route::patch('/{id}/restaurar', [ReceivableController::class, 'restaurar'])->name('restaurar');
        });
    });

    // Cobros contra CxC — mismo flag que receivables/*: sin CxC no hay nada que
    // cobrar, así que todo el grupo (incluido el historial) sigue la misma regla de
    // "apagado = 404 completo" que ya aplica al grupo receivables/* de arriba
    // (REQ-10.9 bis). Rename "Pagos"→"Cobros" (REQ-4.1) — nombres de permiso
    // ('view payments', 'create payments', etc.) se mantienen tal cual: son slugs
    // ya sembrados en roles existentes, renombrarlos es un problema de datos
    // aparte de la reestructuración de rutas/clases de esta fase.
    Route::middleware(['auth', 'module:sales.receivables'])->group(function () {

        Route::get('collections/export', [CollectionController::class, 'export'])
            ->middleware('permission:export payments')
            ->name('collections.export');

        Route::get('collections/eliminados', [CollectionController::class, 'eliminadas'])
            ->middleware('permission:view payments')
            ->name('collections.eliminados');

        Route::get('collections', [CollectionController::class, 'index'])
            ->middleware('permission:view payments')
            ->name('collections.index');

        Route::get('collections/create', [CollectionController::class, 'create'])
            ->middleware('permission:create payments')
            ->name('collections.create');

        Route::post('collections', [CollectionController::class, 'store'])
            ->middleware('permission:create payments')
            ->name('collections.store');

        Route::get('collections/{payment}/print', [CollectionController::class, 'print'])
            ->middleware('permission:print payment receipts')
            ->name('collections.print');

        Route::post('collections/{payment}/cancel', [CollectionController::class, 'cancel'])
            ->middleware('permission:cancel payments')
            ->name('collections.cancel');
    });

    // Dashboard Finanzas movido a routes/app/reports.php como reports.finance
    // (Fase 7.9, sidebar) — vivía bajo app/finance/dashboard, mismo prefijo
    // que el resto de este grupo (Ingresos y Gastos, CxC, Cobros, Facturas,
    // NCF operativo), así que el sidebar resaltaba "Finanzas" Y "Reportes" a
    // la vez al visitarlo. "Ingresos y Gastos" (overview.index, abajo) se
    // queda acá — no es un dashboard de Reportes, es la vista financiera base.

    // Ingresos y Gastos (REQ-03.7) — base, sin gate de módulo: arma sus cifras
    // solo con Sale/InventoryMovement/ClientCollection/Receivable, nunca con JournalEntry.
    // Es la vista financiera universal; el Dashboard de arriba es un extra para
    // quien además tiene contabilidad formal activa.
    Route::get('/overview', FinancialOverviewController::class)
        ->middleware('can:view accounting dashboard')
        ->name('overview.index');

    // routes/app/sales.php (antes) — Facturas, movidas junto con el resto de Finanzas.
    Route::middleware('auth')->group(function () {

        // Exportación de historial
        Route::get('invoices/export', [InvoiceController::class, 'export'])
            ->middleware('permission:export invoices')
            ->name('invoices.export');

        Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview')
            ->middleware(['auth', 'permission:view invoices']);

        // Listado principal con AJAX
        Route::get('invoices', [InvoiceController::class, 'index'])
            ->middleware('permission:view invoices')
            ->name('invoices.index');

        // Visualización de detalle (El documento legal)
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('permission:view invoices')
            ->name('invoices.show');

        // Impresión (Generación de PDF/Ticket)
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])
            ->middleware('permission:print invoices')
            ->name('invoices.print');
    });

    // routes/app/sales.php (antes) — NCF, movido junto con el resto de Finanzas.
    Route::middleware(['auth', 'permission:manage ncf sequences'])->group(function () {

        // En lugar de un middleware Closure, validamos antes de definir el grupo.
        // Si la configuración fiscal está apagada, estas rutas ni siquiera se registran.
        if (module_enabled('sales.ncf')) {

            Route::prefix('ncf')->name('ncf.')->group(function () {

                // Dashboard NCF: movido a routes/app/reports.php como reports.ncf
                // (Fase 7.9, sidebar). De paso se elimina un bug real encontrado acá:
                // este stub (`function () { /* tu controller */ }`) y la ruta real de
                // más abajo (`Route::get('/ncf/dashboard', NcfDashboardController::class)`)
                // definían el MISMO método+URI+nombre — Laravel despacha peticiones
                // entrantes por orden de REGISTRO cuando dos rutas coinciden exacto,
                // así que "Dashboard NCF" respondía con este stub vacío, nunca con el
                // controlador real. El controlador real (movido a reports.php) conserva
                // el permiso que efectivamente protegía la URL (manage ncf sequences,
                // heredado del grupo de este stub).

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
                return redirect()->route('configuration.general.edit')
                    ->with('error', 'La gestión fiscal está desactivada en la configuración general.');
            })->where('any', '.*');
        }
    });
});
