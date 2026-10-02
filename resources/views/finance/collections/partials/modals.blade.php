@foreach($items as $payment)
    {{-- 2. MODAL: ANULACIÓN DE COBRO --}}
    <x-modal name="confirm-cancel-payment-{{ $payment->id }}" maxWidth="sm">
        <form action="{{ route('finance.collections.cancel', $payment) }}" method="POST" class="p-6 text-center">
            @csrf

            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-s-no-symbol class="w-10 h-10"/>
            </div>

            <h3 class="text-lg font-bold text-gray-900">¿Anular este Cobro?</h3>
            <p class="text-sm text-gray-500 mt-2">
                Se anulará el recibo <strong>{{ $payment->receipt_number }}</strong>.
                <span class="block mt-2 font-bold text-red-600 bg-red-50 p-2 rounded border border-red-100">
                    Esto revertirá el saldo de la cuenta por cobrar{{ module_enabled('accounting.advanced') ? ' y anulará su asiento contable' : '' }}.
                </span>
            </p>

            <div class="mt-8 flex justify-center gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">No, mantener</x-ui.button>
                <x-ui.button type="submit" variant="error">Sí, anular cobro</x-ui.button>
            </div>
        </form>
    </x-modal>
@endforeach