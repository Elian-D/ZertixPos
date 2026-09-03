<x-guest-layout>
    {{--
        REQ-3.5, v1.3.0 Fase 3 — bloqueo duro por fecha. Se mantiene liviana y
        sin auth a propósito: EnsureSubscriptionActive redirige acá ANTES de
        que la request pase por 'auth' (mismo criterio que
        EnsureInstallationWizardCompleted con /install), así que un visitante
        sin sesión también tiene que poder verla. La acción real de pagar/
        cambiar de plan (REQ-3.11) vive en `billing.manage`, que sí requiere
        estar logueado — por eso el botón de abajo apunta a uno u otro según
        haya sesión.
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
