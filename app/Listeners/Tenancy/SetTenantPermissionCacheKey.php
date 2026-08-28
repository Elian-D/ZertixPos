<?php

namespace App\Listeners\Tenancy;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Events\TenancyBootstrapped;

// REQ-1.6 (v1.3.0 Fase 1). PermissionRegistrar captura su propio Repository de
// caché una sola vez al boot de la app (antes de que la tenencia se
// inicialice) — CacheTenancyBootstrapper nunca llega a interceptarlo, así que
// sin este listener el caché de roles/permisos de Spatie es un único
// namespace compartido entre todos los tenants + landlord. Cambiar la KEY
// (no el store) es lo que aísla, sin depender de que el driver soporte tags.
class SetTenantPermissionCacheKey
{
    public function handle(TenancyBootstrapped $event): void
    {
        app(PermissionRegistrar::class)->cacheKey =
            config('permission.cache.key').'.tenant.'.$event->tenancy->tenant->getTenantKey();
    }
}
