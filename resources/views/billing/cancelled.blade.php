<x-guest-layout>
    {{-- REQ-3.11 — destino del `cancel_url` cuando el comprador cierra o cancela
         el flujo de aprobación de PayPal antes de terminarlo. --}}
    <div class="flex flex-col items-center text-center gap-4">
        <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center">
            <x-heroicon-o-x-circle class="w-8 h-8 text-amber-500" />
        </div>

        <h1 class="text-xl font-bold text-zertix-secondary">Cancelaste el proceso de pago</h1>

        <p class="text-slate-500 text-sm leading-relaxed">
            No se realizó ningún cargo. Podés intentarlo de nuevo cuando quieras.
        </p>

        <x-ui.button href="{{ route('billing.manage') }}" variant="primary">
            Volver a intentar
        </x-ui.button>
    </div>
</x-guest-layout>
