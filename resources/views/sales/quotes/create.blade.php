<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            
            
            <x-form-header 
                title="Generar Cotización" 
                subtitle="Cree una propuesta formal para su cliente. Podrá convertirla en venta más tarde."
                :back-route="route('sales.quotes.index')" />

            <div class="p-8">
                {{-- 
                    Inyectamos el corazón reactivo. 
                    Pasamos los datos iniciales desde el controlador si fuera necesario, 
                    pero el componente ya está diseñado para ser autónomo.
                --}}
                @livewire('sales.pos.quote-builder')
            </div>

            <div class="p-4 bg-gray-50 border-t flex justify-between items-center text-xs text-gray-400">
                <div class="flex items-center gap-2">
                    <x-icon name="heroicon-o-information-circle" class="w-4 h-4" />
                    <span>Las cotizaciones no restan inventario hasta ser convertidas en venta.</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>