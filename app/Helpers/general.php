<?php

use App\Models\Configuration\ConfiguracionGeneral;

if (! function_exists('general_config')) {

    /**
     * Obtiene la configuración general cacheada en memoria
     */
    function general_config(): ?ConfiguracionGeneral
    {
        static $config = null;

        if ($config === null) {
            try {
                $config = ConfiguracionGeneral::actual();
            } catch (\Throwable $e) {
                // Base de datos sin migrar aún (instalación nueva, migraciones en curso, tests).
                // Varias rutas (ej. routes/app/finance.php) consultan general_config() al
                // registrarse, antes de que exista cualquier fila/tabla — no debe tumbar el boot.
                return null;
            }
        }

        return $config;
    }
}
