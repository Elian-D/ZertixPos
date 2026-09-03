<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\Billing\EnsureSubscriptionActive;
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
    // Después de EnsureInstallationWizardCompleted a propósito — un tenant sin
    // instalar todavía no tiene ninguna Subscription real que evaluar (REQ-3.5).
    EnsureSubscriptionActive::class,
])->group(function () {
    Route::view('/suscripcion/vencida', 'billing.past-due')->name('billing.past-due');

    // REQ-3.11 — vista real de pagar/renovar/cambiar de plan. Requiere auth
    // (a diferencia de billing.past-due, alcanzable sin sesión) — el permiso
    // config.billing se verifica dentro de ManageSubscription::mount(). Wrapper
    // fino + <livewire:billing.manage-subscription />, mismo patrón que el
    // resto de módulos migrados (ARCHITECTURE.md), no ruta directa al
    // componente (a diferencia de InstallWizard, que sí lo hace con su
    // propio ->layout()).
    Route::view('/suscripcion/aprobada', 'billing.approved')->name('billing.approved');
    Route::view('/suscripcion/cancelada', 'billing.cancelled')->name('billing.cancelled');
    Route::view('/suscripcion', 'billing.manage')->middleware('auth')->name('billing.manage');

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
