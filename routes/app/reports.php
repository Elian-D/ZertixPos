<?php

use App\Http\Controllers\Accounting\AccountingDashboardController;
use App\Http\Controllers\Reports\BusinessStatusController;
use App\Http\Controllers\Reports\IncomeStatementController;
use App\Http\Controllers\Reports\InventoryReportController;
use App\Http\Controllers\Reports\NcfReportController;
use App\Http\Controllers\Reports\SalesReportController;
use Illuminate\Support\Facades\Route;

// Reportes (v1.5.0 Fase 6): prefijo propio (app/reports) para que el sidebar no resalte
// también el grupo operativo del módulo (Fase 7.9). Todos con el mismo diseño
// (docs/ui/reports.md): filtros arriba, fila de totales y paneles.
Route::prefix('reports')->as('reports.')->middleware('auth')->group(function () {

    Route::get('/sales', SalesReportController::class)
        ->middleware('permission:sales.view')
        ->name('sales');

    Route::get('/inventory', InventoryReportController::class)
        ->middleware(['module:inventory.tracking', 'permission:inventory.dashboard'])
        ->name('inventory');

    // REQ-6.1: antes "Ingresos y Gastos" (finance/overview), en Finanzas.
    Route::get('/results', IncomeStatementController::class)
        ->middleware('can:accounting.dashboard')
        ->name('results');

    // REQ-6.2
    Route::get('/business', BusinessStatusController::class)
        ->middleware('can:accounting.dashboard')
        ->name('business');

    // `module:` como middleware, no un if en el archivo de rutas: ese corre antes de que la
    // tenencia se inicialice y siempre veía la conexión central (REQ-1.15 de v1.3.0).
    Route::get('/ncf', NcfReportController::class)
        ->middleware(['module:sales.ncf', 'permission:ncf.reports'])
        ->name('ncf');

    // Centro Contable: solo con contabilidad avanzada (apagada). Sin rediseñar en v1.5.0.
    Route::get('/finance', AccountingDashboardController::class)
        ->middleware(['module:accounting.advanced', 'can:accounting.dashboard'])
        ->name('finance');
});
