<?php

use App\Http\Controllers\Billing\InvoicePdfController;
use App\Http\Controllers\Billing\PayPalWebhookController;
use App\Livewire\Install\InstallWizard;
use Illuminate\Support\Facades\Route;

// REQ-4.1/4.6, v1.3.0 Fase 4 — punto de entrada público para aprovisionar un
// tenant NUEVO (self-service o asistido por staff, ?context=assisted). Sin
// middleware 'auth' a propósito: nadie está logueado todavía, el `Tenant` ni
// siquiera existe hasta que se confirma el paso Empresa. Central a
// propósito — el `Tenant` real se crea desde acá, así que no puede vivir
// detrás de InitializeTenancyByDomain (eso requeriría que el tenant ya
// existiera). EnsureInstallationWizardCompleted (routes/tenant.php, Fase 1)
// es un concepto distinto: protege contra reentrar a /install una vez que
// UN tenant específico ya está instalado, no aplica acá.
Route::get('/install', InstallWizard::class)->name('install.wizard');

Route::get('/', function () {
    return view('welcome');
});

// REQ-3.13, v1.3.0 Fase 3 — central a propósito (Subscription/SubscriptionInvoice
// son tablas landlord). Exenta de CSRF en bootstrap/app.php: PayPal no manda
// token, verifica su propia firma adentro (PayPalGateway::handleWebhook()).
Route::post('/webhooks/paypal', PayPalWebhookController::class)->name('webhooks.paypal');

// Fase 4.9, REQ-4.9 — central por el mismo motivo que el webhook de arriba
// (SubscriptionInvoice es landlord). Sin `auth`: es el link que llega por
// correo (InvoicePaid), protegido por la firma de la URL (`signed`), no por
// sesión — el cliente lo abre sin necesitar estar logueado.
Route::get('/facturas/{invoice}/pdf', InvoicePdfController::class)
    ->middleware('signed')
    ->name('billing.invoice.pdf');

// Todo lo que depende del guard `web` (usuarios de negocio) vive en
// routes/tenant.php, no acá — la tabla `users` ahora solo existe por tenant
// (database/migrations/tenant/), ver v1.3.0.md Fase 1, REQ-1.1/REQ-1.7.
