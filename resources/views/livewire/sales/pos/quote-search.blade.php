<div class="relative w-full">
    {{-- .live.debounce.300ms le dice a Livewire: "Espera 300ms después de que el usuario deje de escribir para enviar la petición al servidor" --}}
    <div class="relative">
        <x-ui.forms.input
            type="text"
            icon-left="heroicon-o-magnifying-glass"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por código o nombre..."
        />
    </div>

    {{-- Resultados de búsqueda --}}
    @if(!empty($results))
        <ul class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
            @foreach($results as $product)
                {{-- wire:key es OBLIGATORIO en los bucles --}}
                <li wire:key="search-result-{{ $product['id'] }}">
                    <button 
                        type="button"
                        wire:click="selectProduct({{ $product['id'] }})"
                        class="w-full text-left px-4 py-2 hover:bg-blue-50 focus:bg-blue-50 transition-colors flex justify-between items-center"
                    >
                        <div>
                            <span class="block font-medium text-gray-900">{{ $product['name'] }}</span>
                            <span class="block text-sm text-gray-500">Cod: {{ $product['sku'] }}</span>
                        </div>
                        <span class="font-bold text-gray-700">{{ config('regional.currency_symbol') }}{{ number_format($product['price'], 2) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</div>