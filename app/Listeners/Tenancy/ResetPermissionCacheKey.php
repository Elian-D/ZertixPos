<?php

namespace App\Listeners\Tenancy;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Events\TenancyEnded;

// Contraparte de SetTenantPermissionCacheKey (REQ-1.6) — vuelve a la key por
// defecto cuando la tenencia termina, para que un worker de cola que procese
// varios tenants en el mismo proceso PHP no arrastre la key del tenant
// anterior al volver a contexto central.
class ResetPermissionCacheKey
{
    public function handle(TenancyEnded $event): void
    {
        app(PermissionRegistrar::class)->cacheKey = config('permission.cache.key');
    }
}
