{{--
    x-ui.account-banner (REQ-3.10, v1.3.0 Fase 3) — 3 variantes excluyentes,
    calculadas acá mismo (sin props, mismo patrón que x-ui.breadcrumbs):

    1. demo       — tenants.is_demo, siempre visible, sin urgencia.
    2. vencida    — tenants.scheduled_deletion_at no nulo (ya está en los 90
                     días de retención, REQ-3.8). Convive con el bloqueo duro
                     de REQ-3.5 — el usuario ya está en /suscripcion/vencida,
                     este banner es el que explica el conteo de días ahí.
    3. trial      — Subscription en 'trialing' con pocos días antes de que
                     current_period_ends_at la venza (TRIAL_WARNING_DAYS).

    El CTA de "vencida"/"trial" apunta a `billing.manage` (REQ-3.11) — la
    vista real de pagar/renovar/cambiar de plan. El banner solo se renderiza
    dentro de `x-app-layout` (ver ese archivo), así que quien lo ve ya está
    autenticado — a diferencia del botón de `billing.past-due`, que sí tiene
    que resolver entre login/manage porque esa pantalla es alcanzable sin sesión.
--}}
@php
    $variant = null;
    $daysRemaining = null;

    // Últimos N días del trial en los que se avisa — REQ-3.10 solo da un
    // rango de ejemplo ("3-5 de los 15"), no un número exacto: se elige el
    // extremo alto (5) para avisar con más margen, no menos.
    $trialWarningDays = 5;

    $currentTenant = tenant();

    if ($currentTenant) {
        if ($currentTenant->is_demo) {
            $variant = 'demo';
        } elseif ($currentTenant->scheduled_deletion_at) {
            $variant = 'past_due';
            $daysRemaining = max(0, (int) ceil(now()->diffInSeconds($currentTenant->scheduled_deletion_at, false) / 86400));
        } else {
            $subscription = \App\Models\Landlord\Subscription::where('tenant_id', $currentTenant->getTenantKey())
                ->latest('id')
                ->first();

            if ($subscription?->status === 'trialing' && $subscription->current_period_ends_at) {
                $daysLeft = (int) ceil(now()->diffInSeconds($subscription->current_period_ends_at, false) / 86400);

                if ($daysLeft >= 0 && $daysLeft <= $trialWarningDays) {
                    $variant = 'trial_ending';
                    $daysRemaining = $daysLeft;
                }
            }
        }
    }
@endphp

@if ($variant === 'demo')
    <div class="flex items-center justify-center gap-2 px-4 py-2 text-sm bg-sky-50 text-sky-700 border-b border-sky-100">
        <x-heroicon-o-information-circle class="w-4 h-4 shrink-0" />
        <span>Estás viendo una cuenta de demostración.</span>
    </div>
@elseif ($variant === 'trial_ending')
    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-sm bg-amber-50 text-amber-800 border-b border-amber-100">
        <div class="flex items-center gap-2">
            <x-heroicon-o-clock class="w-4 h-4 shrink-0" />
            <span>
                @if ($daysRemaining <= 0)
                    Tu período de prueba termina hoy.
                @else
                    Tu período de prueba termina en {{ $daysRemaining }} {{ Str::plural('día', $daysRemaining) }}.
                @endif
            </span>
        </div>
        <a href="{{ route('billing.manage') }}" class="font-semibold underline hover:no-underline">
            Elegir un plan
        </a>
    </div>
@elseif ($variant === 'past_due')
    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-sm bg-red-50 text-red-700 border-b border-red-100">
        <div class="flex items-center gap-2">
            <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0" />
            <span>
                Cuenta vencida — se borrará en {{ $daysRemaining }} {{ Str::plural('día', $daysRemaining) }} si no se regulariza el pago.
            </span>
        </div>
        <a href="{{ route('billing.manage') }}" class="font-semibold underline hover:no-underline">
            Regularizar pago
        </a>
    </div>
@endif
