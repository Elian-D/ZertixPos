<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Cliente --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
            <select wire:model="clientId" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="">Seleccione un cliente...</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
            @error('clientId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Buscador de Productos (Inyectamos el otro componente aquí) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Producto</label>
            @livewire('sales.pos.quote-search')
        </div>
    </div>

    {{-- Tabla de Productos --}}
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 w-32">Precio</th>
                    <th class="px-4 py-3 w-32">Cant.</th>
                    <th class="px-4 py-3 w-32">Desc. (%)</th>
                    <th class="px-4 py-3 w-32">Subtotal</th>
                    <th class="px-4 py-3 w-16"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                    <tr wire:key="item-row-{{ $item['product_id'] }}-{{ $index }}" class="border-b">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                        <td class="px-4 py-3">{{ config('regional.currency_symbol') }}{{ number_format($item['price'], 2) }}</td>
                        <td class="px-4 py-3">
                            <input type="number" 
                                wire:model.live.debounce.500ms="items.{{ $index }}.quantity" 
                                min="1" 
                                class="w-full border-gray-300 rounded text-sm p-1">
                            @error("items.{$index}.quantity") 
                                <span class="text-red-500 text-xs block mt-1 leading-tight">{{ $message }}</span> 
                            @enderror
                        </td>
                        <td class="px-4 py-3">
                            <div class="relative rounded-md shadow-sm">
                                <input type="number" 
                                    wire:model.live.debounce.500ms="items.{{ $index }}.discount_percentage" 
                                    min="0" 
                                    max="100"
                                    step="0.01"
                                    {{-- 11.2: cotización de backoffice, sin terminal asociada — sin límite de
                                         descuento a propósito (ver QuoteBuilder::recalculateTotals()). --}}
                                    class="w-full border-gray-300 rounded text-sm p-1 pr-7 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed text-right">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <span class="text-gray-400 text-xs">%</span>
                                </div>
                            </div>
                            @error("items.{$index}.discount_percentage") 
                                <span class="text-red-500 text-xs block mt-1 leading-tight">{{ $message }}</span> 
                            @enderror
                        </td>
                        <td class="px-4 py-3 font-bold text-gray-900">
                            {{ config('regional.currency_symbol') }}{{ number_format($item['subtotal'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700">
                                <x-icon name="heroicon-o-trash" class="w-5 h-5"/>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                            No hay productos en la cotización. Usa el buscador de arriba.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @error('items') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
    </div>

    {{-- Resumen y Acción --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div class="w-full md:w-1/2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas (Opcional)</label>
            <textarea wire:model="notes" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <div class="w-full md:w-1/3 bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between mb-2">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-medium">{{ config('regional.currency_symbol') }}{{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between mb-2 text-red-500">
                <span>Descuento</span>
                <span>-{{ config('regional.currency_symbol') }}{{ number_format($discountTotal, 2) }}</span>
            </div>
            <div class="flex justify-between mt-4 pt-4 border-t border-gray-200">
                <span class="text-lg font-bold">Total</span>
                <span class="text-xl font-black">{{ config('regional.currency_symbol') }}{{ number_format($total, 2) }}</span>
            </div>

            {{-- NUEVO: Bloque de Errores Críticos / Generales del Servidor --}}
            @error('general')
                <div class="mt-4 p-2.5 bg-red-50 border-l-4 border-red-500 rounded text-red-700 text-xs flex items-start gap-2 animate-fade-in">
                    <x-icon name="heroicon-m-exclamation-triangle" class="w-4 h-4 flex-shrink-0 mt-0.5 text-red-500"/>
                    <div>{{ $message }}</div>
                </div>
            @enderror

            <button 
                wire:click="saveQuote" 
                wire:loading.attr="disabled"
                class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg disabled:opacity-50 flex items-center justify-center gap-2 transition-colors duration-150"
            >
                <span wire:loading.remove wire:target="saveQuote">Guardar Cotización</span>
                <span wire:loading wire:target="saveQuote" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Guardando...
                </span>
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-lg flex items-center gap-2">
            <x-icon name="heroicon-o-check-circle" class="w-5 h-5 text-green-600"/>
            <span>{{ session('success') }}</span>
        </div>
    @endif
</div>