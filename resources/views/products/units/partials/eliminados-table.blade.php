{{-- 1. La Tabla limpia --}}
<x-data-table 
    :items="$items" 
    :headers="['nombre' => 'Unidad de medida', 'deleted_at' => 'Eliminada']"
    :visibleColumns="['nombre', 'deleted_at', 'acciones']">

    @forelse($items as $units)
        <tr class="hover:bg-gray-50 transition duration-150 group">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 mr-3">
                        <x-heroicon-s-tag class="w-4 h-4"/>
                    </div>
                    <div class="text-sm font-bold text-gray-900">{{ $units->name }}</div>
                </div>
            </td>

            <td class="px-6 py-4 text-sm text-gray-500 italic">
                {{ $units->deleted_at->diffForHumans() }}
            </td>

            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    <form action="{{ route('inventory.products.units.restaurar', $units->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $units->id }}')"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="px-6 py-12 text-center text-gray-500">No hay elementos.</td>
        </tr>
    @endforelse
</x-data-table>

{{-- 2. Los Modales FUERA de la tabla --}}
@foreach($items as $units)
    <x-ui.confirm-deletion-modal 
        :id="$units->id"
        :title="'¿Eliminar Permanentemente?'"
        :itemName="$units->name"
        :route="route('inventory.products.units.borrarDefinitivo', $units->id)"
        :description="'Estás a punto de borrar definitivamente la unidad de medida <strong>' . $units->name . '</strong>.'"
    >
        <strong>Aviso Crítico:</strong> Esta operación borrará todos los datos asociados y no se puede deshacer.
    </x-ui.confirm-deletion-modal>
@endforeach