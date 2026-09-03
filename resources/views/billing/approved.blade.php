<x-guest-layout>
    {{--
        REQ-3.11 — destino del `return_url` que PayPal usa tras la aprobación
        del comprador (PayPalGateway::createSubscription()). No activa nada acá
        — la activación real (status pasa a active) llega por separado vía el
        webhook (REQ-3.13), que puede tardar unos segundos en procesarse.
    --}}
    <div class="flex flex-col items-center text-center gap-4">
        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center">
            <x-heroicon-o-check-circle class="w-8 h-8 text-green-600" />
        </div>

        <h1 class="text-xl font-bold text-zertix-secondary">¡Listo! PayPal aprobó el pago</h1>

        <p class="text-slate-500 text-sm leading-relaxed">
            Tu suscripción se está activando — puede tardar unos segundos en reflejarse. Si en unos minutos seguís viendo el plan anterior, contactá a soporte.
        </p>

        <x-ui.button href="{{ route('dashboard') }}" variant="primary">
            Ir al panel
        </x-ui.button>
    </div>
</x-guest-layout>
