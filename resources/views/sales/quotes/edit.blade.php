<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            
            <x-ui.toasts />
            
            <x-form-header 
                :title="'Editar Cotización #' . $quote->id" 
                :subtitle="'Cliente: ' . $quote->customer->name"
                :back-route="route('sales.quotes.index')" />

            {{-- Alerta de estado si la cotización ya no es borrador --}}
            @if($quote->status !== 'draft')
                <div class="mx-8 mt-6 p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-700 flex items-center gap-3">
                    <x-icon name="heroicon-s-exclamation-triangle" class="w-5 h-5" />
                    <p class="text-sm font-medium">
                        Esta cotización está en estado <strong>{{ strtoupper($quote->status) }}</strong>. 
                        Cualquier cambio aquí podría afectar registros históricos.
                    </p>
                </div>
            @endif

            <div class="p-8">
                {{-- Pasamos la propiedad quote al componente --}}
                @livewire('pos.quote-builder', ['quote' => $quote])
            </div>

            <div class="p-6 bg-gray-50 border-t flex justify-between items-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold">
                    Creada por: {{ $quote->user->name }} | {{ $quote->created_at->format('d/m/Y H:i') }}
                </span>
                
                <div class="flex gap-3">
                    <a href="{{ route('sales.quotes.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                        Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>