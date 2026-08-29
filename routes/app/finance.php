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

        Route::middleware('permission:accounting_accounts.manage')->group(function () {

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
                ->middleware('permission:journal_entries.view')
                ->name('journal_entries.index');

            Route::get('journal_entries/create', [JournalEntryController::class, 'create'])
                ->middleware('permission:journal_entries.create')
                ->name('journal_entries.create');

            Route::post('journal_entries', [JournalEntryController::class, 'store'])
                ->middleware('permission:journal_entries.create')
                ->name('journal_entries.store');

            Route::get('journal_entries/{journal_entry}/edit', [JournalEntryController::class, 'edit'])
                ->middleware('permission:journal_entries.edit')
                ->name('journal_entries.edit');

            Route::put('journal_entries/{journal_entry}', [JournalEntryController::class, 'update'])
                ->middleware('permission:journal_entries.edit')
                ->name('journal_entries.update');

            Route::patch('journal_entries/{journal_entry}/post', [JournalEntryController::class, 'post'])
                ->middleware('permission:journal_entries.post')
                ->name('journal_entries.post');

            Route::patch('journal_entries/{journal_entry}/cancel', [JournalEntryController::class, 'cancel'])
                ->middleware('permission:journal_entries.cancel')
                ->name('journal_entries.cancel');

            Route::get('journal_entries/export', [JournalEntryController::class, 'export'])
                ->middleware('permission:journal_entries.view')
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
                ->middleware('permission:receivables.view')
                ->name('index');

            // Sin destroy/eliminados/restaurar/borrarDefinitivo — Receivable es
            // Categoría C (docs/analisis/politica-soft-deletes.md): bitácora de
            // deuda del cliente, nunca se borra ni se archiva. El `status` de la
            // fila (incl. `cancelled`, reflejo de la venta anulada) es toda la
            // "eliminación" que existe. Corrección 2026-08-27 sobre el intento
            // inicial de esta migración, que la había tratado como Categoría B.
        });
    });

    // Cobros contra CxC — mismo flag que receivables/*: sin CxC no hay nada que
    // cobrar, así que todo el grupo (incluido el historial) sigue la misma regla de
    // "apagado = 404 completo" que ya aplica al grupo receivables/* de arriba
    // (REQ-10.9 bis). Rename "Pagos"→"Cobros" (REQ-4.1) — nombres de permiso
    // ('collections.view', 'collections.create', etc.) se mantienen tal cual: son slugs
    // ya sembrados en roles existentes, renombrarlos es un problema de datos
    // aparte de la reestructuración de rutas/clases de esta fase.
    Route::middleware(['auth', 'module:sales.receivables'])->group(function () {

        // collections.export/eliminados reemplazadas por CollectionTable::export()
        // (Excel::download() devuelto directo desde la acción Livewire) del mismo
        // índice — ver App\Livewire\App\Finance\CollectionTable. Sin destroy ni
        // papelera: Categoría C, ver docs/analisis/politica-soft-deletes.md.

        Route::get('collections', [CollectionController::class, 'index'])
            ->middleware('permission:collections.view')
            ->name('collections.index');

        Route::get('collections/create', [CollectionController::class, 'create'])
            ->middleware('permission:collections.create')
            ->name('collections.create');

        Route::post('collections', [CollectionController::class, 'store'])
            ->middleware('permission:collections.create')
            ->name('collections.store');

        Route::get('collections/{payment}/print', [CollectionController::class, 'print'])
            ->middleware('permission:collections.print_receipt')
            ->name('collections.print');

        Route::post('collections/{payment}/cancel', [CollectionController::class, 'cancel'])
            ->middleware('permission:collections.cancel')
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
        ->middleware('can:accounting.dashboard')
        ->name('overview.index');

    // routes/app/sales.php (antes) — Facturas, movidas junto con el resto de Finanzas.
    Route::middleware('auth')->group(function () {

        // invoices.export reemplazada por InvoiceTable::export() del mismo índice
        // (Excel::download() devuelto directo desde la acción Livewire) — ver
        // App\Livewire\App\Finance\InvoiceTable.

        Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview')
            ->middleware(['auth', 'permission:invoices.view']);

        // Listado principal con AJAX
        Route::get('invoices', [InvoiceController::class, 'index'])
            ->middleware('permission:invoices.view')
            ->name('invoices.index');

        // Visualización de detalle (El documento legal)
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('permission:invoices.view')
            ->name('invoices.show');

        // Impresión (Generación de PDF/Ticket)
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])
            ->middleware('permission:invoices.print')
            ->name('invoices.print');
    });

    // routes/app/sales.php (antes) — NCF, movido junto con el resto de Finanzas.
    //
    // REQ-1.15 (v1.3.0 Fase 1) — corrección real: antes este bloque gateaba con
    // `if (module_enabled('sales.ncf')) { ... } else { fallback }` directo en el
    // archivo de rutas. Ese `if` corre UNA VEZ, al registrar las rutas (cuando
    // TenancyServiceProvider::mapRoutes() carga routes/tenant.php en el callback
    // `booted()`) — y eso pasa SIEMPRE antes de que InitializeTenancyByDomain
    // inicialice la tenencia para esa misma petición (el registro de rutas
    // precede al despacho de middleware). Así que module_enabled() ahí consultaba
    // siempre la conexión central (sin `installation_modules`, solo por tenant
    // desde REQ-1.7) y caía en `false` el 100% de las veces — las rutas reales de
    // NCF nunca llegaban a registrarse bajo tenencia, sin importar lo que dijera
    // el tenant real. Se corrige usando `module:sales.ncf` como MIDDLEWARE (igual
    // que `accounting.advanced`/`sales.receivables` arriba en este mismo archivo)
    // — ese chequeo corre en tiempo de request, después de que la tenencia ya
    // está inicializada.
    Route::middleware(['auth', 'permission:ncf_sequences.manage', 'module:sales.ncf'])
        ->prefix('ncf')->name('ncf.')->group(function () {

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
                // logs.export.excel reemplazada por NcfLogTable::exportExcel() del
                // mismo índice — ver App\Livewire\App\Finance\NcfLogTable.
                Route::get('/export/txt', [NcfLogController::class, 'exportTxt'])->name('export.txt');
            });

            Route::group(['prefix' => 'types', 'as' => 'types.'], function () {
                Route::get('/', [NcfTypeController::class, 'index'])->name('index');
                Route::post('/', [NcfTypeController::class, 'store'])->name('store');
                Route::put('/{ncfType}', [NcfTypeController::class, 'update'])->name('update');
            });
        });
});
