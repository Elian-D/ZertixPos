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
        $cache ??= InstallationModule::pluck('is_enabled', 'module_key');

        return (bool) ($cache[$key] ?? false);
    }
}

if (! function_exists('current_plan')) {
    /**
     * Resuelve qué Plan tiene asignada esta instalación (configuraciones_generales.plan_id).
     */
    function current_plan(): ?Plan
    {
        $planId = general_config()?->plan_id;

        return $planId ? Plan::find($planId) : null;
    }
}
