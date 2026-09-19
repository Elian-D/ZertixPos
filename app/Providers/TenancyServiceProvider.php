<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\Tenancy\ResetPermissionCacheKey;
use App\Listeners\Tenancy\SetTenantPermissionCacheKey;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
                ResetPermissionCacheKey::class, // contraparte de SetTenantPermissionCacheKey (REQ-1.6)
            ],

            Events\BootstrappingTenancy::class => [],
            // REQ-1.6: PermissionRegistrar captura su propio Repository de caché al
            // boot, antes de que CacheTenancyBootstrapper pueda interceptarlo — sin
            // esto el caché de roles/permisos de Spatie es un solo namespace
            // compartido entre todos los tenants + landlord.
            Events\TenancyBootstrapped::class => [
                SetTenantPermissionCacheKey::class,
            ],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();

        $this->makeTenancyMiddlewareHighestPriority();
        $this->bootLivewireUpdateRoute();
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }

    /**
     * REQ-1.13 (v1.3.0 Fase 1). Livewire registra su propia ruta de
     * actualización AJAX (`default-livewire.update`) con solo `->middleware('web')`
     * — nunca corre `InitializeTenancyByDomain`, así que cualquier wire:click/
     * wire:model sobre un tenant intenta resolver Auth::user() contra la
     * conexión central, que no tiene tabla `users` (solo existe por tenant
     * desde REQ-1.7). Se registra una ruta propia ANTES de que Livewire
     * registre la suya (o, si ya la registró, la reemplaza como la que el
     * frontend realmente usa — Livewire::setUpdateRoute() prioriza la última
     * ruta nombrada "*livewire.update" sobre la "default-livewire.update").
     */
    protected function bootLivewireUpdateRoute()
    {
        // Sin PreventAccessFromCentralDomains a propósito: esta misma ruta
        // también la necesita el futuro Panel de Súper Admin (Fase 5), que
        // corre en el dominio central — esa middleware la bloquearía ahí.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', Middleware\InitializeTenancyByDomain::class])
                ->name('tenant.livewire.update');
        });

        // Por defecto, InitializeTenancyByDomain relanza la excepción cuando
        // el dominio no matchea ningún tenant — correcto para las rutas de
        // negocio (routes/tenant.php), pero esta ruta es compartida: un
        // request desde un dominio central (landlord) nunca va a matchear un
        // tenant, y ahí NO es un error, es el caso esperado. Solo se traga la
        // excepción cuando el dominio es realmente central — cualquier otro
        // dominio desconocido (typo, subdominio no registrado) sigue
        // fallando como antes, no se relaja esa protección.
        Middleware\InitializeTenancyByDomain::$onFail = function ($exception, $request, $next) {
            if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
                return $next($request);
            }

            throw $exception;
        };
    }
}
