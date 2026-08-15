<x-data-table 
    :items="$units" 
    :headers="$allColumns" 
    :visibleColumns="$visibleColumns" 
    :bulkActions="false"
>
    @forelse($units as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">

            {{-- ID --}}
            @if(in_array('id', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono text-gray-500">
                    {{ $item->id }}
                </td>
            @endif

            {{-- Nombre --}}
            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {{ $item->name }}
                </td>
            @endif

            @if(in_array('abbreviation', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->abbreviation ?? '—' }}</td>
            @endif
            
            {{-- Estado --}}
            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-bold
                        {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
            @endif

            {{-- Fechas --}}
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

            {{-- Acciones --}}
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <form action="{{ route('products.units.toggle', $item) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="text-xs px-2 py-1 rounded border {{ $item->is_active ? 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100' : 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' }}">
                            {{ $item->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>

                    <button @click="$dispatch('open-modal', 'edit-unit-{{ $item->id }}')" class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50">
                        <x-heroicon-s-pencil class="w-5 h-5" />
                    </button>

                    <button
                        @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                        class="p-1 rounded text-red-600 hover:text-red-900 hover:bg-red-50">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10"
                class="text-center py-10 text-gray-500 italic">
                No hay unidades de medidas registradas
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('products.units.partials.modals')
