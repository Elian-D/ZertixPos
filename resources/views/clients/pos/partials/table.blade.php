<x-data-table :items="$pos" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="$bulkActions">
    @forelse($pos as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            @if($bulkActions)
                <td class="px-4 py-4 text-center">
                    <input type="checkbox" value="{{ $item->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                </td>
            @endif

            @if(in_array('code', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono text-gray-500">{{ $item->code }}</td>
            @endif

            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->name }}</td>
            @endif

            @if(in_array('client_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->client->name ?? '—' }}</td>
            @endif

            @if(in_array('business_type_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->businessType->nombre ?? '—' }}</td>
            @endif

            @if(in_array('provincia_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->provincia->name ?? '—' }}</td>
            @endif

            @if(in_array('city', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->city ?? '—' }}</td>
            @endif

            @if(in_array('contact_name', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->contact_name ?? '—' }}</td>
            @endif

            @if(in_array('contact_phone', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->contact_phone ?? '—' }}</td>
            @endif


            @if(in_array('active', $visibleColumns))
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-bold {{ $item->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $item->active ? 'Activo' : 'Inactivo' }}
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


            <td class="px-6 py-4">
                <div class="flex items-center gap-3">

                    <button @click="$dispatch('open-modal', 'view-pos-{{ $item->id }}')" 
                            class="bg-gray-100 text-gray-600 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm"
                            title="Ver detalles completos">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>

                    <a href="{{ route('clients.pos.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 p-2 rounded-full hover:bg-indigo-50">
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
            <td colspan="10" class="text-center py-10 text-gray-500 italic">No hay puntos de venta registrados</td>
        </tr>
    @endforelse
</x-data-table>
@include('clients.pos.partials.modals')