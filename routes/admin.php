<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin (Súper Admin) Routes
|--------------------------------------------------------------------------
|
| Fase 5 (REQ-5) — corre en el dominio central (requerido desde
| routes/web.php, no routes/tenant.php: ver TenancyServiceProvider::
| bootLivewireUpdateRoute() y docs/features/v1.3.0.md §Fase 5). Guard
| `landlord` (App\Models\Landlord\Admin), separado del guard `web` de
| negocio — no comparte sesión con ningún tenant.
|
*/

Route::prefix('admin')->as('admin.')->group(function () {
    Route::middleware('guest:landlord')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:landlord')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        // Sin create/edit/destroy — REQ-5.2 (plan de solo lectura) y REQ-5.6
        // (el botón "Nuevo Tenant" enlaza al wizard público /install en
        // pestaña nueva, ver docs/features/v1.3.0.md §4.3/§5.6).
        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
    });
});
