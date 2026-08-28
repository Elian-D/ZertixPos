<?php

use App\Livewire\Install\InstallWizard;
use Illuminate\Support\Facades\Route;

// Sin middleware 'auth' — nadie puede loguearse todavía en una instalación
// sin terminar (Fase 8). EnsureInstallationWizardCompleted (bootstrap/app.php)
// es lo único que la protege: bloquea reingresar una vez ya instalado.
// Queda en el dominio central a propósito — Fase 4 (REQ-4.1) envuelve este
// wizard para el flujo multi-tenant, no se toca antes de esa fase.
Route::get('/install', InstallWizard::class)->name('install.wizard');

Route::get('/', function () {
    return view('welcome');
});

// Todo lo que depende del guard `web` (usuarios de negocio) vive en
// routes/tenant.php, no acá — la tabla `users` ahora solo existe por tenant
// (database/migrations/tenant/), ver v1.3.0.md Fase 1, REQ-1.1/REQ-1.7.
