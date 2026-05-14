<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Importante importar esto

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * PROGRAMACIÓN DE TAREAS (Task Scheduling)
 * Este bloque se encarga de ejecutar comandos de forma automática.
 */

// Ejecuta el comando de expiración de cotizaciones todos los días a la medianoche
Schedule::command('quotes:expire')->daily();

/* * NOTA PARA EL SERVIDOR (BanaHosting / cPanel):
 * Para que esto funcione en producción, debes agregar un "Cron Job" en tu cPanel.
 * * El comando que debes pegar en el cPanel es:
 * * * * * * cd /home/tu_usuario/ruta_de_tu_proyecto && php artisan schedule:run >> /dev/null 2>&1
 * * Explicación: Ese comando de cPanel se ejecuta cada minuto, pero Laravel es quien 
 * decide internamente si hoy a las 00:00 le toca ejecutarse a 'quotes:expire'.
 */