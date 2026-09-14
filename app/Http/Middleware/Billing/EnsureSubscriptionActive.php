<?php

namespace App\Http\Middleware\Billing;

use App\Models\Landlord\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REQ-3.5, v1.3.0 Fase 3 — bloqueo por fecha, no por status de webhook.
 *
 * Regla única: `now() > current_period_ends_at` → bloquea y redirige a
 * "regulariza tu pago". Nunca se compara `subscription->status` — ese campo
 * solo lo actualiza el webhook de PayPal (PayPalGateway::handleWebhook(),
 * REQ-3.13) o el job de reconciliación (REQ-3.6), y ninguno de los dos
 * garantiza avisar a tiempo. Un tenant sin ninguna fila de `subscriptions`
 * (o sin `current_period_ends_at`) se trata igual que uno vencido — no hay
 * fecha válida que demuestre que está al día.
 *
 * Mismo patrón que EnsureInstallationWizardCompleted: corre después de
 * InitializeTenancyByDomain (necesita `tenant()` resuelto), se salta a sí
 * mismo por nombre de ruta para no loopear, y no corre en testing (las BDs
 * de test no siembran ninguna Subscription — bloquearía los 27+ Feature
 * tests existentes de golpe, mismo problema real que ya documentó esa clase).
 */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $tenant = tenant();

        // Fuera de contexto tenant (no debería pasar, esta clase solo se
        // registra dentro de routes/tenant.php) — nada que bloquear.
        // is_demo (REQ-3.9, adelantado en la migración de REQ-3.4): el tenant
        // demo nunca vence, no tiene Subscription real.
        if (! $tenant || $tenant->is_demo) {
            return $next($request);
        }

        $isLivewireInternal = str_contains($request->route()?->getName() ?? '', 'livewire');

        // Bug real encontrado en vivo (2026-09-06, probando Escenario B de
        // REQ-4.8): `routes/auth.php` se registra DENTRO de este mismo grupo
        // de middleware (routes/tenant.php), así que sin este bypass
        // `/login` también quedaba bloqueado — un visitante sin sesión con
        // el tenant vencido caía en `billing.past-due`, cuyo botón (sin
        // `auth()->check()`) apunta de vuelta a `route('login')`: un loop
        // cerrado, sin forma real de entrar a pagar. `logout`/`password.*`/
        // `verification.*` por el mismo motivo — ninguna de estas rutas
        // puede depender de que la suscripción esté al día.
        //
        // Segunda vuelta del mismo bug (2026-09-06, encontrada verificando
        // con `curl` crudo después de que el fix por nombre "no alcanzó"):
        // `Route::post('login', ...)` (routes/auth.php:17) y
        // `Route::post('confirm-password', ...)` (línea 47) **no tienen
        // `->name(...)`** — el bypass de arriba, que compara por nombre de
        // ruta, nunca los alcanzaba. `Auth::attempt()` ni corría: el
        // middleware cortaba antes de que `AuthenticatedSessionController::store()`
        // se ejecutara, así que el POST de login "funcionaba" (302) pero
        // jamás autenticaba a nadie — el mismo loop de antes, ahora en el
        // paso del formulario en vez del link. Por eso el chequeo de acá
        // usa el PATH (`$request->is()`), no el nombre — cubre ambos
        // verbos (GET/POST) de una sola vez, con o sin nombre.
        $isAuthRoute = $request->is('login', 'logout', 'confirm-password', 'forgot-password', 'reset-password*', 'verify-email*', 'email/verification-notification', 'password');

        // billing.* completo, no solo billing.past-due (REQ-3.11 agrega
        // billing.manage/approved/cancelled) — todas tienen que quedar
        // alcanzables para un tenant bloqueado, si no, no hay forma de pagar.
        if ($isLivewireInternal || $isAuthRoute || $request->routeIs('billing.*')) {
            return $next($request);
        }

        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->latest('id')
            ->first();

        $currentPeriodEndsAt = $subscription?->current_period_ends_at;

        if ($currentPeriodEndsAt === null || now()->gt($currentPeriodEndsAt)) {
            // Rediseño (2026-09-07): un usuario YA AUTENTICADO va directo al
            // resumen real (`billing.manage` — ManageSubscription, ahora
            // dentro de <x-app-layout>, adapta su propio banner/CTA a
            // "vencido"), no al placeholder de `billing.past-due`. Ese
            // placeholder queda reservado para el único caso que de verdad
            // lo necesita: un visitante SIN sesión, que no puede ver "su"
            // resumen porque no hay ninguna sesión de la cual leerlo — para
            // ese caso, `billing.past-due` sigue siendo la pantalla mínima
            // de siempre (ver su propio docblock).
            return redirect()->route(auth()->check() ? 'billing.manage' : 'billing.past-due');
        }

        return $next($request);
    }
}
