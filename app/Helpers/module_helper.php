<?php

use App\Models\Configuration\InstallationModule;
use App\Models\Configuration\Plan;

if (! function_exists('module_enabled')) {
    /**
     * Indica si un módulo satélite está activo en esta instalación.
     * Módulos base (nunca registrados en config/modules.php) no pasan por aquí.
     */
    function module_enabled(string $key): bool
    {
        static $cache = null;

        if ($cache === null) {
            try {
                $cache = InstallationModule::pluck('is_enabled', 'module_key');
            } catch (\Throwable $e) {
                // Base de datos sin migrar aún (instalación nueva, migraciones en curso,
                // migrate:fresh en progreso) — routes/app/sales.php consulta este helper
                // al registrarse, antes de que installation_modules exista. Mismo criterio
                // que general_config() (ver app/Helpers/general.php).
                return false;
            }
        }

        return (bool) ($cache[$key] ?? false);
    }
}

if (! function_exists('current_plan')) {
    /**
     * Resuelve qué Plan tiene asignada esta instalación. `plan_id` vive en
     * `tenants` (landlord), no en `configuraciones_generales` (REQ-3.4,
     * v1.3.0 Fase 3) — `tenant()` es el helper de stancl/tenancy, resuelve
     * al tenant activo de la request.
     */
    function current_plan(): ?Plan
    {
        $planId = tenant()?->plan_id;

        return $planId ? Plan::find($planId) : null;
    }
}
