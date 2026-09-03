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

        // billing.* completo, no solo billing.past-due (REQ-3.11 agrega
        // billing.manage/approved/cancelled) — todas tienen que quedar
        // alcanzables para un tenant bloqueado, si no, no hay forma de pagar.
        if ($isLivewireInternal || $request->routeIs('billing.*')) {
            return $next($request);
        }

        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->latest('id')
            ->first();

        $currentPeriodEndsAt = $subscription?->current_period_ends_at;

        if ($currentPeriodEndsAt === null || now()->gt($currentPeriodEndsAt)) {
            return redirect()->route('billing.past-due');
        }

        return $next($request);
    }
}
