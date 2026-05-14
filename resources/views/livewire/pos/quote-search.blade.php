<div class="relative w-full">
    {{-- .live.debounce.300ms le dice a Livewire: "Espera 300ms después de que el usuario deje de escribir para enviar la petición al servidor" --}}
    <div class="relative">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <x-icon name="heroicon-o-magnifying-glass" class="w-5 h-5 text-gray-400"/>
        </div>
        <input 
            type="text" 
            wire:model.live.debounce.300ms="search" 
            placeholder="Buscar por código o nombre..." 
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
        >
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
                        <span class="font-bold text-gray-700">${{ number_format($product['price'], 2) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</div>