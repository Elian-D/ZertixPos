<x-data-table :items="$products" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="$bulkActions">
    @forelse($products as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            @if($bulkActions)
                <td class="px-4 py-4 text-center">
                    <input type="checkbox" value="{{ $item->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                </td>
            @endif

            @if(in_array('sku', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-gray-700">{{ $item->sku }}</td>
            @endif

            @if(in_array('name', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $item->name }}</td>
            @endif

            @if(in_array('image_path', $visibleColumns))
                <td class="px-6 py-4">
                    @if($item->image_path)
                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover shadow-sm">
                    @else
                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                            <x-heroicon-s-photo class="w-6 h-6" />
                        </div>
                    @endif
                </td>
            @endif

            @if(in_array('category_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ $item->category->name ?? 'S/C' }}</span>
                </td>
            @endif

            @if(in_array('description', $visibleColumns))
                <td class="px-6 py-4 text-sm font-medium text-gray-600">
                    {{ $item->description ?? 'N/A'}}
                </td>
            @endif

            @if(in_array('price', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">
                    {{ general_config()->currency_symbol }} {{ number_format($item->price, 2) }}
                </td>
            @endif

            @if(in_array('cost', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">
                    {{ general_config()->currency_symbol }} {{ number_format($item->cost, 2) }}
                </td>
            @endif

            @if(in_array('unit_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->unit->name ?? '—' }} ({{ $item->unit->abbreviation ?? '' }})</td>
            @endif

            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-bold {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
            @endif

            @if(in_array('is_stockable', $visibleColumns))
                <td class="px-6 py-4">
                    <span
                        class="inline-flex whitespace-nowrap px-2 py-1 text-xs rounded-full font-bold
                        {{ $item->is_stockable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $item->is_stockable ? 'Con stock' : 'Sin stock' }}
                    </span>
                </td>
            @endif

            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->updated_at->diffForHumans() }}
                </td>
            @endif


            {{-- Columna de Acciones --}}
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <button @click="$dispatch('open-modal', 'view-product-{{ $item->id }}')" 
                            class="bg-gray-100 text-gray-600 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>

                    <a href="{{ route('products.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 p-2 rounded-full hover:bg-indigo-50">
                        <x-heroicon-s-pencil class="w-5 h-5" />
                    </a>
                    
                    <button @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')" class="text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-10 text-gray-500 italic">No hay productos que coincidan con los criterios.</td>
        </tr>
    @endforelse
</x-data-table>
@include('products.partials.modals')
