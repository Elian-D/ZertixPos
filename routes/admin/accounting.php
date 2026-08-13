<?php

use App\Http\Controllers\Accounting\AccountingAccountController;
use App\Http\Controllers\Accounting\AccountingDashboardController;
use App\Http\Controllers\Accounting\FinancialOverviewController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\PaymentController;
use App\Http\Controllers\Accounting\ReceivableController;
use Illuminate\Support\Facades\Route;

Route::prefix('accounting')->as('accounting.')->group(function () {

    // Plan de Cuentas y Asientos — contabilidad formal, satélite (REQ-03.5). Nunca envuelve
    // receivables/payments: CxC y su abono operativo son base, ver config/modules.php.
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

    // document_types se movió a routes/admin/configuration.php (REQ-10.3) — es un
    // catálogo del sistema completo, no algo específico de Contabilidad.

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

    // Pagos (cobros contra CxC) — mismo flag que receivables/*: sin CxC no hay nada que
    // cobrar, así que todo el grupo (incluido el historial) sigue la misma regla de
    // "apagado = 404 completo" que ya aplica al grupo receivables/* de arriba
    // (REQ-10.9 bis).
    Route::middleware(['auth', 'module:sales.receivables'])->group(function () {

        Route::get('payments/export', [PaymentController::class, 'export'])
            ->middleware('permission:export payments')
            ->name('payments.export');

        Route::get('payments/eliminados', [PaymentController::class, 'eliminadas'])
            ->middleware('permission:view payments')
            ->name('payments.eliminados');

        Route::get('payments', [PaymentController::class, 'index'])
            ->middleware('permission:view payments')
            ->name('payments.index');

        Route::get('payments/create', [PaymentController::class, 'create'])
            ->middleware('permission:create payments')
            ->name('payments.create');

        Route::post('payments', [PaymentController::class, 'store'])
            ->middleware('permission:create payments')
            ->name('payments.store');

        Route::get('payments/{payment}/print', [PaymentController::class, 'print'])
            ->middleware('permission:print payment receipts')
            ->name('payments.print');

        Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])
            ->middleware('permission:cancel payments')
            ->name('payments.cancel');
    });

    // Dashboard financiero — hoy solo reporta balances de partida doble (JournalItem por
    // rol de cuenta), sin sentido si accounting.advanced está apagado (ver REQ-03.4/03.7:
    // el reporte simple de Ingresos/Gastos, sin JournalEntry, es lo que lo reemplaza).
    Route::middleware('module:accounting.advanced')->group(function () {
        Route::get('/dashboard', AccountingDashboardController::class)
            ->middleware('can:view accounting dashboard')
            ->name('dashboard.index');
    });

    // Ingresos y Gastos (REQ-03.7) — base, sin gate de módulo: arma sus cifras
    // solo con Sale/InventoryMovement/Payment/Receivable, nunca con JournalEntry.
    // Es la vista financiera universal; el Dashboard de arriba es un extra para
    // quien además tiene contabilidad formal activa.
    Route::get('/overview', FinancialOverviewController::class)
        ->middleware('can:view accounting dashboard')
        ->name('overview.index');
});
