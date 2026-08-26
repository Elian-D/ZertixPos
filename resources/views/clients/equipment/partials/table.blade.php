<x-data-table 
    :items="$equipments" 
    :headers="$allColumns" 
    :visibleColumns="$visibleColumns" 
    :bulkActions="$bulkActions"
>
    @forelse($equipments as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">

            {{-- Bulk checkbox --}}
            @if($bulkActions)
                <td class="px-4 py-4 text-center">
                    <input type="checkbox"
                           value="{{ $item->id }}"
                           class="row-checkbox rounded border-gray-300 text-zertix-primary-600 focus:ring-zertix-primary-500 cursor-pointer">
                </td>
            @endif

            {{-- Código --}}
            @if(in_array('code', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono text-gray-500">
                    {{ $item->code }}
                </td>
            @endif

            {{-- Nombre --}}
            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {{ $item->name }}
                </td>
            @endif

            {{-- Tipo de Equipo --}}
            @if(in_array('equipment_type_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $item->equipmentType->nombre ?? '—' }}
                </td>
            @endif

            {{-- POS asignado --}}
            @if(in_array('point_of_sale_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $item->pointOfSale->name ?? '—' }}
                </td>
            @endif

            {{-- Serial --}}
            @if(in_array('serial_number', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $item->serial_number ?? '—' }}
                </td>
            @endif

            {{-- Modelo --}}
            @if(in_array('model', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $item->model ?? '—' }}
                </td>
            @endif


            {{-- Estado --}}
            @if(in_array('active', $visibleColumns))
                <td class="px-6 py-4">
                    <x-ui.badge :variant="$item->active ? 'success' : 'error'" size="sm">
                        {{ $item->active ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
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

                    <button
                        @click="$dispatch('open-modal', 'view-equipment-{{ $item->id }}')"
                        class="bg-gray-100 text-gray-600 hover:bg-zertix-primary-600 hover:text-white p-2 rounded-full transition shadow-sm"
                        title="Ver detalles">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>

                    <a href="{{ route('clients.equipment.edit', $item) }}"
                       class="text-zertix-primary-600 hover:text-zertix-primary-900 p-2 rounded-full hover:bg-zertix-primary-50">
                        <x-heroicon-s-pencil class="w-5 h-5" />
                    </a>

                    <button
                        @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                        class="text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10"
                class="text-center py-10 text-gray-500 italic">
                No hay equipos registrados
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('clients.equipment.partials.modals')
