<x-guest-layout>
    {{--
        REQ-3.5, v1.3.0 Fase 3 — bloqueo duro por fecha. Se mantiene liviana y
        sin auth a propósito: EnsureSubscriptionActive redirige acá ANTES de
        que la request pase por 'auth' (mismo criterio que
        EnsureInstallationWizardCompleted con /install), así que un visitante
        sin sesión también tiene que poder verla.

        **Decisión final (2026-09-07, tras el rediseño de REQ-3.11):** esta
        vista NO es un placeholder pendiente de reemplazo — es, a propósito,
        la única pantalla para el caso sin sesión: no hay "su" resumen que
        mostrarle a alguien sin autenticar. `EnsureSubscriptionActive` ahora
        manda a cualquier usuario YA AUTENTICADO directo al resumen real
        (`billing.manage` — ManageSubscription, con estado/plan/factura de
        verdad, dentro de `<x-app-layout>`), nunca acá. El branch
        `auth()->check()` del botón de abajo queda como red de seguridad
        (si alguien autenticado llega a esta URL a mano), no como el camino
        esperado.
    --}}

    {{-- REQ-3.10 — el banner de "cuenta vencida" convive con este bloqueo
         duro: es el mismo componente que aparece arriba del layout de la
         app, acá es el que explica el conteo de días antes del borrado
         (retención de 90 días, REQ-3.8), no una superficie aparte. --}}
    <x-ui.account-banner />

    <div class="flex flex-col items-center text-center gap-4">
        <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center">
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-amber-500" />
        </div>

        <h1 class="text-xl font-bold text-zertix-secondary">Tu suscripción venció</h1>

        <p class="text-slate-500 text-sm leading-relaxed">
            El acceso a ZertixPOS está pausado hasta que se regularice el pago.
        </p>

        <x-ui.button
            :href="auth()->check() ? route('billing.manage') : route('login')"
            variant="primary"
        >
            {{ auth()->check() ? 'Regularizar pago' : 'Iniciar sesión' }}
        </x-ui.button>
    </div>
</x-guest-layout>
