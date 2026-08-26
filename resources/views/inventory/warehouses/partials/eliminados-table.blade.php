{{-- 1. La Tabla limpia --}}
<x-data-table
    :items="$items"
    :headers="['nombre' => 'Almacén', 'deleted_at' => 'Eliminado']"
    :visibleColumns="['nombre', 'deleted_at', 'acciones']">

    @forelse($items as $warehouse)
        <tr class="hover:bg-gray-50 transition duration-150 group">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 mr-3">
                        @if($warehouse->type === 'static')
                            <x-heroicon-s-building-office-2 class="w-4 h-4"/>
                        @elseif($warehouse->type === 'pos')
                            <x-heroicon-s-shopping-cart class="w-4 h-4"/>
                        @else
                            <x-heroicon-s-truck class="w-4 h-4"/>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900">{{ $warehouse->name }}</div>
                        <div class="text-xs text-gray-500">{{ $warehouse->type_label }}</div>
                    </div>
                </div>
            </td>

            <td class="px-6 py-4 text-sm text-gray-500 italic">
                {{ $warehouse->deleted_at->diffForHumans() }}
            </td>

            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Botón Restaurar --}}
                    <form action="{{ route('inventory.warehouses.restaurar', $warehouse->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" title="Restaurar Almacén" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    {{-- Botón Eliminar Definitivo --}}
                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $warehouse->id }}')"
                            title="Eliminar permanentemente"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-archive-box-x-mark class="w-12 h-12 text-gray-300 mb-2" />
                    <p>No hay almacenes en la papelera.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

{{-- 2. Los Modales FUERA de la tabla --}}
@foreach($items as $warehouse)
    <x-ui.confirm-deletion-modal 
        :id="$warehouse->id"
        :title="'¿Eliminar Almacén Permanentemente?'"
        :itemName="$warehouse->name"
        :route="route('inventory.warehouses.borrarDefinitivo', $warehouse->id)"
        :description="'Estás a punto de borrar definitivamente el almacén <strong>' . $warehouse->name . '</strong>.'"
    >
        <div>
            <strong>Aviso Crítico:</strong> Esta operación es irreversible. Si este almacén tuvo movimientos de inventario históricos, podrías perder coherencia en reportes antiguos.
        </div>
    </x-ui.confirm-deletion-modal>
@endforeach