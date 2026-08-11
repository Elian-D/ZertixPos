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

        // Corre antes que cualquier ruta del grupo 'web' — sin esto, una instalación
        // recién migrada (sin ConfiguracionGeneral.nombre_empresa real) dejaría entrar
        // a /login o /admin/* directo, sin pasar nunca por el Wizard (Fase 8).
        $middleware->web(append: [
            \App\Http\Middleware\EnsureInstallationWizardCompleted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
