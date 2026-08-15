<x-data-table 
    :items="$items" 
    :headers="['name' => 'Punto de Venta', 'client' => 'Cliente', 'deleted_at' => 'Eliminado']"
    :visibleColumns="['name', 'client', 'deleted_at']">

    @forelse($items as $pos)
        <tr class="hover:bg-gray-50 transition duration-150 group">
            {{-- Nombre del Punto de Venta --}}
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 mr-3">
                        <x-heroicon-s-map-pin class="w-4 h-4"/>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900">{{ $pos->name }}</div>
                        <div class="text-xs text-gray-500">{{ $pos->city }}</div>
                    </div>
                </div>
            </td>

            {{-- Cliente asociado --}}
            <td class="px-6 py-4 text-sm text-gray-600">
                <div class="flex flex-col">
                    <span class="font-medium">{{ $pos->client->name ?? 'Sin cliente' }}</span>
                    <span class="text-xs text-gray-400">{{ $pos->client->tax_id ?? '' }}</span>
                </div>
            </td>

            {{-- Fecha de eliminación --}}
            <td class="px-6 py-4 text-sm text-gray-500 italic">
                {{ $pos->deleted_at->diffForHumans() }}
            </td>

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Restaurar --}}
                    <form action="{{ route('clients.delivery_points.restore', $pos->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" title="Restaurar Punto de Venta"
                                class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    {{-- Borrado Definitivo --}}
                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $pos->id }}')"
                            title="Eliminar permanentemente"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>

    @empty
        <tr>
            <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-map-pin class="w-12 h-12 text-gray-200 mb-3"/>
                    <p class="text-gray-500 font-medium">No hay puntos de venta en la papelera.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@foreach($items as $pos)

    <x-ui.confirm-deletion-modal 
        :id="$pos->id"
        :title="'¿Eliminar Permanentemente?'"
        :itemName="$pos->name"
        :route="route('clients.delivery_points.borrarDefinitivo', $pos->id)"
        :description="'Estás a punto de borrar definitivamente el punto de venta <strong>' . $pos->name . '</strong>.'"
    >
        <strong>Aviso Crítico:</strong> Esta operación borrará todos los datos asociados y no se puede deshacer.
    </x-ui.confirm-deletion-modal>
@endforeach