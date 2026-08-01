<x-data-table 
    :items="$items" 
    :headers="['name' => 'Terminal', 'warehouse' => 'Almacén', 'deleted_at' => 'Eliminado']"
    :visibleColumns="['name', 'warehouse', 'deleted_at']">

    @forelse($items as $terminal)
        <tr class="hover:bg-gray-50 transition duration-150 group">
            {{-- Info de la Terminal --}}
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 mr-3 border border-slate-100">
                        <x-heroicon-s-computer-desktop class="w-5 h-5 opacity-50"/>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900 italic line-through decoration-gray-400">{{ $terminal->name }}</div>
                        <div class="text-xs text-gray-500">Cuenta: {{ $terminal->cashAccount->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </td>

            {{-- Almacén Relacionado --}}
            <td class="px-6 py-4 text-sm text-gray-600">
                <span class="px-2 py-1 bg-gray-50 border border-gray-100 rounded text-xs">
                    {{ $terminal->warehouse->name ?? 'Sin almacén' }}
                </span>
            </td>

            {{-- Fecha de eliminación --}}
            <td class="px-6 py-4 text-sm text-gray-500 italic">
                {{ $terminal->deleted_at->diffForHumans() }}
            </td>

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Restaurar --}}
                    <form action="{{ route('sales.pos.terminals.restore', $terminal->id) }}" method="POST">
                        @csrf
                        <button type="submit" title="Restaurar Terminal"
                                class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-transparent hover:border-emerald-100">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    {{-- Borrado Definitivo --}}
                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $terminal->id }}')"
                            title="Eliminar permanentemente"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-archive-box class="w-12 h-12 text-gray-200 mb-3"/>
                    <p class="text-gray-500 font-medium">No hay terminales en la papelera.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@foreach($items as $terminal)
    <x-ui.confirm-deletion-modal 
        :id="$terminal->id"
        :title="'¿Eliminar Terminal Permanentemente?'"
        :itemName="$terminal->name"
        :route="route('sales.pos.terminals.force-delete', $terminal->id)"
        :description="'Vas a borrar definitivamente la terminal <strong>' . $terminal->name . '</strong>. No podrá ser recuperada.'"
    >
            <p class="text-sm text-amber-700">
                <strong>Advertencia:</strong> Si esta terminal tiene registros históricos de ventas, el borrado permanente podría causar inconsistencias en los reportes de "Cierre de Caja". Se recomienda dejarla en la papelera o simplemente desactivarla.
            </p>
    </x-ui.confirm-deletion-modal>
@endforeach