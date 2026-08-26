<x-data-table 
    :items="$stocks" 
    :headers="$allColumns" 
    :visibleColumns="$visibleColumns" 
    :bulkActions="false"
>
    @forelse($stocks as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">

            {{-- Producto --}}
            @if(in_array('product_id', $visibleColumns))
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8 bg-gray-100 rounded-md flex items-center justify-center">
                            @if($item->product->image_path)
                                <img class="h-8 w-8 rounded-md object-cover" src="{{ $item->product->image_url }}" alt="">
                            @else
                                <x-heroicon-o-cube class="w-5 h-5 text-gray-400" />
                            @endif
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-semibold text-gray-900">{{ $item->product->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->product->category->name }}</div>
                        </div>
                    </div>
                </td>
            @endif

            {{-- Almacén --}}
            @if(in_array('warehouse_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                    {{ $item->warehouse->name }}
                </td>
            @endif

            {{-- Cantidad Actual --}}
            @if(in_array('quantity', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4">
                    <div class="text-sm font-black text-gray-900">
                        {{ number_format($item->quantity, 2) }} 
                        <span class="text-[10px] text-gray-400 font-normal uppercase italic">
                            {{ $item->product->unit->abbreviation }}
                        </span>
                    </div>
                </td>
            @endif

            {{-- Stock Mínimo --}}
            @if(in_array('min_stock', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {{ number_format($item->min_stock, 2) }}
                </td>
            @endif

            {{-- Estado (Cálculo Visual) --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $status = 'ok';
                        if($item->quantity <= 0) $status = 'out';
                        elseif($item->quantity <= $item->min_stock) $status = 'low';
                    @endphp

                    <x-ui.badge :variant="match($status) { 'ok' => 'success', 'low' => 'warning', default => 'error' }" size="sm">
                        {{ match($status) { 'ok' => 'Suficiente', 'low' => 'Stock Bajo', default => 'Agotado' } }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Fechas --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->created_at->format('d/m/Y') }}
                </td>
            @endif

            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-3">
                    <button @click="$dispatch('open-modal', 'view-stock-{{ $item->id }}')" class="text-gray-400 hover:text-zertix-primary-600 transition">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>
                    
                    <button @click="$dispatch('open-modal', 'edit-min-stock-{{ $item->id }}')" 
                    class="text-zertix-primary-600 hover:text-zertix-primary-900 p-2 rounded-lg hover:bg-zertix-primary-50 transition-colors"
                    title="Ajustar Stock Mínimo">
                    <x-heroicon-s-bell-alert class="w-5 h-5" />
                </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="text-center py-12">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-building-office class="w-12 h-12 text-gray-200 mb-2" />
                    <p class="text-gray-500 font-medium">No hay registros</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>
@include('inventory.stocks.partials.modals')