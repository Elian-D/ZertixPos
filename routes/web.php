<?php

use App\Http\Controllers\Billing\PayPalWebhookController;
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

// REQ-3.13, v1.3.0 Fase 3 — central a propósito (Subscription/SubscriptionInvoice
// son tablas landlord). Exenta de CSRF en bootstrap/app.php: PayPal no manda
// token, verifica su propia firma adentro (PayPalGateway::handleWebhook()).
Route::post('/webhooks/paypal', PayPalWebhookController::class)->name('webhooks.paypal');

// Todo lo que depende del guard `web` (usuarios de negocio) vive en
// routes/tenant.php, no acá — la tabla `users` ahora solo existe por tenant
// (database/migrations/tenant/), ver v1.3.0.md Fase 1, REQ-1.1/REQ-1.7.
