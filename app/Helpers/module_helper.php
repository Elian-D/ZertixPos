<?php

use App\Models\Configuration\InstallationModule;

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
