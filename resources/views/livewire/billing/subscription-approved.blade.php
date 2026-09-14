<div class="flex flex-col items-center text-center gap-4 max-w-md mx-auto py-10"
     @if (! $activated && ! $notFound && $attempts < \App\Livewire\Billing\SubscriptionApproved::MAX_ATTEMPTS)
        wire:poll.3s="retry"
     @endif
>
    @if ($notFound)
        <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center">
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-amber-500" />
        </div>

        <h1 class="text-xl font-bold text-zertix-secondary">No pudimos identificar el pago</h1>
        <p class="text-gray-500 text-sm leading-relaxed">
            PayPal no nos devolvió el identificador de la suscripción. Si ya aprobaste el pago, no te preocupes —
            va a activarse solo apenas confirmemos el cobro.
        </p>

        <x-ui.button href="{{ route('billing.manage') }}" variant="primary">
            Ir a mi suscripción
        </x-ui.button>
    @elseif ($activated)
        <div class="w-16 h-16 bg-zertix-primary/10 rounded-full flex items-center justify-center">
            <x-heroicon-s-check-circle class="w-8 h-8 text-zertix-primary" />
        </div>

        <h1 class="text-xl font-bold text-zertix-secondary">¡Listo! Tu suscripción está activa</h1>
        <p class="text-gray-500 text-sm leading-relaxed">
            PayPal confirmó el pago y tu plan ya está activo. Podés seguir usando ZertixPOS sin interrupciones.
        </p>

        <x-ui.button href="{{ route('billing.manage') }}" variant="primary" iconRight="heroicon-s-arrow-right">
            Ver mi suscripción
        </x-ui.button>
    @else
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center">
            <x-heroicon-s-arrow-path class="w-8 h-8 text-gray-400 animate-spin" />
        </div>

        <h1 class="text-xl font-bold text-zertix-secondary">Confirmando tu pago con PayPal...</h1>
        <p class="text-gray-500 text-sm leading-relaxed">
            PayPal ya aprobó tu pago — estamos confirmando la activación. Esto no debería tardar más de unos segundos.
        </p>

        @if ($attempts >= \App\Livewire\Billing\SubscriptionApproved::MAX_ATTEMPTS)
            <p class="text-xs text-gray-400">
                Está tardando más de lo normal. Podés reintentar o seguir de todos modos — se activa solo apenas se confirme.
            </p>
            <div class="flex items-center gap-3">
                <x-ui.button type="button" variant="secondary" appearance="outline" wire:click="retry">
                    Reintentar
                </x-ui.button>
                <x-ui.button href="{{ route('billing.manage') }}" variant="primary">
                    Ir a mi suscripción
                </x-ui.button>
            </div>
        @endif
    @endif
</div>
