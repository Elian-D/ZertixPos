<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // POS Middlewares
            'pos.config.integrity' => \App\Http\Middleware\Sales\Pos\EnsurePosConfig::class,
            'pos.session' => \App\Http\Middleware\Sales\Pos\EnsurePosSession::class,
            'check.terminal.access' => \App\Http\Middleware\Sales\Pos\CheckTerminalAccess::class,

            // Módulos base/satélite
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
        ]);

        // EnsureInstallationWizardCompleted YA NO va acá (v1.3.0 Fase 1, REQ-1.1/1.7).
        // El grupo 'web' corre ANTES de que InitializeTenancyByDomain cambie la
        // conexión — si el middleware vivía acá, consultaba ConfiguracionGeneral
        // siempre contra la conexión central, que ya no tiene esa tabla (es de
        // negocio, solo existe por tenant). Se registra ahora dentro de
        // routes/tenant.php, después de InitializeTenancyByDomain, para que
        // consulte la base del tenant correcto. Ver v1.3.0.md Fase 1.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
