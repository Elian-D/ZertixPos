<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureInstallationWizardCompleted;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Todo lo que antes vivía en routes/web.php como negocio real del cliente
| (dashboard, perfil, login/auth, y los módulos de routes/app/*.php) corre
| acá, no en el dominio central — el guard `web` (App\Models\User) ahora
| solo existe por tenant (database/migrations/tenant/), ver v1.3.0.md
| Fase 1, REQ-1.1/REQ-1.7.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // Después de InitializeTenancyByDomain a propósito — necesita la conexión
    // del tenant ya activa para consultar su propia ConfiguracionGeneral.
    EnsureInstallationWizardCompleted::class,
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified', 'permission:dashboard.view'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Ruta temporal — Fase 7, REQ-7.5: mockup de x-ui.forms.* en contexto real de la app
    // (sidebar, header, breadcrumbs, footer), sin lógica de guardado. Quitar cuando los
    // formularios reales ya usen estos componentes y este demo deje de hacer falta.
    Route::view('/demo/form', 'examples.form-components-demo')->middleware('auth')->name('demo.form');

    require __DIR__.'/auth.php';

    // Rutas administrativas (panel) — antes vivía en RouteServiceProvider::boot()
    Route::middleware('auth')
        ->prefix('app')
        ->group(function () {
            foreach (glob(base_path('routes/app/*.php')) as $routeFile) {
                require $routeFile;
            }
        });
});
