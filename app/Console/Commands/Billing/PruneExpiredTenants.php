<?php

namespace App\Console\Commands\Billing;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * REQ-3.12, v1.3.0 Fase 3 — borra los tenants cuya retención de 90 días
 * (REQ-3.8, `tenants.scheduled_deletion_at`) ya se cumplió. Acción
 * destructiva e irreversible: cada borrado queda loggeado ANTES de
 * ejecutarse (tenant, fecha de vencimiento, nombre) — no debe fallar en
 * silencio ni sin rastro (ver docs/features/v1.3.0.md §3.12).
 *
 * `$tenant->delete()` por sí solo NO borra la base física, solo la fila
 * `tenants` — el borrado real de la base lo dispara
 * `Stancl\Tenancy\Jobs\DeleteDatabase`, ya registrado en el pipeline del
 * evento `TenantDeleted` en `TenancyServiceProvider` desde REQ-1.1 (no hacía
 * falta agregarlo, solo confirmarlo).
 *
 * `is_demo` excluido explícitamente aunque hoy nunca debería tener
 * `scheduled_deletion_at` (el middleware de REQ-3.5 nunca lo deja pasar por
 * el job de reconciliación) — defensa en profundidad, no un caso real.
 */
class PruneExpiredTenants extends Command
{
    protected $signature = 'tenants:prune-expired';

    protected $description = 'Borra (tenant + base física) los tenants cuya retención de 90 días ya venció';

    public function handle(): int
    {
        $tenants = Tenant::where('is_demo', false)
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now())
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No hay tenants vencidos para borrar.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $context = [
                'tenant_id' => $tenant->getTenantKey(),
                'scheduled_deletion_at' => $tenant->scheduled_deletion_at?->toDateTimeString(),
                'business_name' => $tenant->business_name,
            ];

            Log::warning('PruneExpiredTenants: borrando tenant por retención vencida.', $context);
            $this->warn("Borrando tenant «{$tenant->getTenantKey()}» (venció el {$tenant->scheduled_deletion_at?->toDateString()})...");

            try {
                $tenant->delete();
                $this->info('  -> borrado correctamente (tenant + base física).');
            } catch (Throwable $e) {
                Log::error('PruneExpiredTenants: fallo al borrar un tenant.', $context + ['error' => $e->getMessage()]);
                $this->error("  -> FALLÓ: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
