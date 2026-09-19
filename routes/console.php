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

// REQ-3.6, v1.3.0 Fase 3 — marca past_due explícito, no bloquea (eso lo hace
// EnsureSubscriptionActive por fecha, sin depender de este job).
Schedule::command('subscriptions:reconcile')->daily();

// REQ-3.12, v1.3.0 Fase 3 — borra (tenant + base física) lo que ya cumplió
// la retención de 90 días. Corre a la 01:00, después de reconcile (00:00),
// para no depender del orden de ejecución entre dos tareas a la misma hora
// exacta. Probado contra un tenant descartable, nunca contra dev/test2/demo
// (ver docs/features/v1.3.0.md §3.12) — acción destructiva e irreversible.
Schedule::command('tenants:prune-expired')->dailyAt('01:00');

/* * NOTA PARA EL SERVIDOR (BanaHosting / cPanel):
 * Para que esto funcione en producción, debes agregar un "Cron Job" en tu cPanel.
 * * El comando que debes pegar en el cPanel es:
 * * * * * * cd /home/tu_usuario/ruta_de_tu_proyecto && php artisan schedule:run >> /dev/null 2>&1
 * * Explicación: Ese comando de cPanel se ejecuta cada minuto, pero Laravel es quien 
 * decide internamente si hoy a las 00:00 le toca ejecutarse a 'quotes:expire'.
 */