{{-- Modal de anulación de venta — lo incluyen la tabla de ventas y el show.
     El motivo es siempre obligatorio y se guarda en la venta (REQ-3.18). Con NCF
     se elige de la lista DGII (también va al 608); sin NCF es texto libre. --}}
@foreach($items as $sale)
@php
    $hasNcf = $sale->has_ncf ?? ($sale->relationLoaded('ncfLog') ? $sale->ncfLog !== null : $sale->ncfLog()->exists());
@endphp
<x-modal name="confirm-cancel-sale-{{ $sale->id }}" maxWidth="sm">
    <form action="{{ route('sales.cancel', $sale) }}" method="POST" class="p-6">
        @csrf
        @method('PATCH')

        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <x-heroicon-s-exclamation-triangle class="w-10 h-10"/>
        </div>

        <div class="text-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">¿Anular venta?</h3>
            <p class="text-xs text-gray-500 mt-1">
                Venta: <strong>{{ $sale->number }}</strong>
            </p>
        </div>

        <div class="mt-4 text-left">
            @if($hasNcf)
                <x-ui.forms.select label="Motivo de anulación (DGII)" name="cancellation_reason" required
                    hint="Se reporta a la DGII en la anulación del NCF">
                    <option value="01 - ERRORES DE DIGITACION">01 - Errores de digitación</option>
                    <option value="02 - ERRORES DE IMPRESION">02 - Errores de impresión</option>
                    <option value="03 - PRODUCTO DEFECTUOSO">03 - Producto defectuoso</option>
                    <option value="04 - DEVOLUCION">04 - Devolución</option>
                    <option value="05 - OTROS">05 - Otros</option>
                </x-ui.forms.select>
            @else
                <x-ui.forms.textarea label="Motivo de anulación" name="cancellation_reason" rows="3" required minlength="5" maxlength="255"
                    placeholder="Ej.: venta registrada por error" />
            @endif
        </div>

        <div class="mt-8 flex justify-center gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Volver</x-ui.button>
            <x-ui.button type="submit" variant="error">Confirmar anulación</x-ui.button>
        </div>
    </form>
</x-modal>
@endforeach
