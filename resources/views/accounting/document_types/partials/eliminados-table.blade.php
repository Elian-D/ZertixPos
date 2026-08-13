<x-data-table 
    :items="$items" 
    :headers="['code' => 'Sigla', 'name' => 'Nombre del Documento', 'deleted_at' => 'Fecha Eliminación']"
    :visibleColumns="['code', 'name', 'deleted_at']">

    @forelse($items as $item)
        <tr class="hover:bg-red-50/30 transition duration-150 group border-b border-gray-50">
            <td class="px-6 py-4 text-sm font-mono font-bold text-gray-500 uppercase">
                <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200">{{ $item->code }}</span>
            </td>
            
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 mr-3">
                        <x-heroicon-s-document-text class="w-4 h-4"/>
                    </div>
                    <div class="text-sm font-bold text-gray-700">{{ $item->name }}</div>
                </div>
            </td>

            <td class="px-6 py-4 text-sm text-gray-400 italic">
                {{ $item->deleted_at->format('d/m/Y H:i') }} 
                <span class="text-[10px] block text-gray-300">{{ $item->deleted_at->diffForHumans() }}</span>
            </td>

            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Botón Restaurar --}}
                    <form action="{{ route('configuration.document_types.restaurar', $item->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" title="Restaurar Tipo de Documento" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    {{-- Botón Eliminar Definitivo --}}
                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                            title="Borrado permanente"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="px-6 py-16 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <x-heroicon-o-trash class="w-12 h-12 text-gray-200 mb-2" />
                    <p class="font-medium text-gray-400">No hay documentos en la papelera.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@foreach($items as $item)
    <x-ui.confirm-deletion-modal 
        :id="$item->id"
        :title="'¿Borrar Documento Permanentemente?'"
        :itemName="$item->code . ' - ' . $item->name"
        :route="route('configuration.document_types.borrarDefinitivo', $item->id)"
    >
        <div>
            <p class="text-sm text-red-600 font-bold uppercase tracking-tighter">Acción Irreversible</p>
            <p class="text-xs text-gray-600 leading-relaxed">
                Si eliminas permanentemente el tipo <strong>{{ $item->name }}</strong>, cualquier configuración asociada se perderá para siempre.
            </p>
            <p class="text-[11px] bg-red-50 p-3 border border-red-100 text-red-700 rounded-lg italic">
                Nota: Esta acción solo es posible si no existen documentos contables reales utilizando este tipo en el histórico.
            </p>
        </div>
    </x-ui.confirm-deletion-modal>
@endforeach