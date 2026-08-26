<?php

use App\Http\Controllers\Accounting\AccountingDashboardController;
use App\Http\Controllers\Inventory\InventoryDashboardController;
use App\Http\Controllers\Sales\Ncf\NcfDashboardController;
use App\Http\Controllers\Sales\SalesDashboardController;
use Illuminate\Support\Facades\Route;

// Extraído de sales.php/inventory.php/finance.php (Fase 7.9, sidebar). Los 4
// dashboards de "Reportes" compartían el mismo prefijo de URL que su módulo
// operativo de origen (app/sales*, app/inventory*, app/finance*) — el sidebar
// resaltaba el grupo operativo Y "Reportes" a la vez al visitarlos (bug real).
// Prefijo propio y exclusivo en vez de listas de exclusión manuales en el
// sidebar. "Ingresos y Gastos" (finance.overview.index) NO se mueve acá — es
// la vista financiera base, no un dashboard de Reportes, se queda en Finanzas.
//
// De paso: finance.ncf.dashboard tenía un stub muerto duplicado en finance.php
// (mismo método+URI+nombre que la ruta real, registrado ANTES) — Laravel
// despacha por orden de registro cuando dos rutas coinciden exacto, así que la
// URL respondía con el stub vacío, no con NcfDashboardController. Se elimina
// el stub al migrar; el controlador real conserva el permiso que efectivamente
// protegía la URL antes (manage ncf sequences, heredado del grupo del stub).
Route::prefix('reports')->as('reports.')->group(function () {

    Route::middleware('auth')->get('/sales', SalesDashboardController::class)
        ->name('sales');

    Route::middleware('module:inventory.tracking')->group(function () {
        Route::get('/inventory', InventoryDashboardController::class)
            ->middleware('permission:view inventory dashboard')
            ->name('inventory');
    });

    Route::middleware('module:accounting.advanced')->group(function () {
        Route::get('/finance', AccountingDashboardController::class)
            ->middleware('can:view accounting dashboard')
            ->name('finance');
    });

    if (module_enabled('sales.ncf')) {
        Route::get('/ncf', NcfDashboardController::class)
            ->middleware(['auth', 'permission:manage ncf sequences'])
            ->name('ncf');
    }
});
