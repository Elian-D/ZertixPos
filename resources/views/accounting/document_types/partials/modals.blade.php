@foreach($items as $item)

{{-- MODAL VER TIPO DE DOCUMENTO --}}
<x-modal name="view-document-type-{{ $item->id }}" maxWidth="2xl">
    <div class="overflow-hidden rounded-xl">
        {{-- Header con degradado --}}
        <div class="bg-gradient-to-r from-indigo-50 to-white px-8 py-6 border-b relative">
            <div class="flex justify-between items-start">
                <div class="flex gap-4 items-center">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                        <x-heroicon-s-document-text class="w-7 h-7"/>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $item->name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-semibold px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded uppercase tracking-wider">
                                {{ $item->code }}
                            </span>
                            <span class="text-gray-400 text-xs">•</span>
                            <span class="text-sm text-gray-500 font-medium">
                                Prefijo: {{ $item->prefix ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ring-1 ring-inset shadow-sm 
                    {{ $item->is_active ? 'bg-emerald-100 text-emerald-700 ring-emerald-600/20' : 'bg-red-100 text-red-700 ring-red-600/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-2 {{ $item->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></span>
                    {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>

        <div class="p-8 bg-white">
            <div class="grid grid-cols-1 gap-8">

                {{-- Configuración de Numeración --}}
                <div class="space-y-6">
                    <section>
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <x-heroicon-s-hashtag class="w-4 h-4"/> Control de Numeración
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-4 shadow-inner">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase font-bold block tracking-tighter">Último Número Generado</span>
                                <p class="text-lg font-mono font-bold text-gray-700">{{ number_format($item->current_number, 0) }}</p>
                            </div>
                            <div class="pt-2 border-t border-gray-200">
                                <span class="text-[10px] text-indigo-400 uppercase font-bold block tracking-tighter">Próximo a Emitir</span>
                                <p class="text-lg font-mono font-bold text-indigo-600">{{ number_format($item->current_number + 1, 0) }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Auditoría Simple --}}
            <div class="mt-8 pt-6 border-t flex justify-between items-center">
                <div class="flex flex-col">
                    <span class="text-[10px] text-gray-400 uppercase">Última modificación</span>
                    <span class="text-xs font-medium text-gray-600">{{ $item->updated_at->format('d/m/Y h:i A') }}</span>
                </div>
                <div class="flex gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
                    @can('edit document types')
                    <a href="{{ route('accounting.document_types.edit', $item) }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold uppercase hover:bg-indigo-700 transition shadow-md shadow-indigo-100">
                        <x-heroicon-s-pencil class="w-3 h-3 mr-2" /> Editar
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-modal>

{{-- MODAL ELIMINAR --}}
<x-ui.confirm-deletion-modal 
    :id="$item->id"
    :title="'¿Eliminar Tipo de Documento?'"
    :itemName="$item->name"
    :type="'el tipo de documento'"
    :route="route('accounting.document_types.destroy', $item)"
>
    <strong>Atención:</strong> Si eliminas este tipo de documento, no podrás emitir nuevos registros bajo esta sigla. Los documentos existentes en el sistema no se borrarán.
</x-ui.confirm-deletion-modal>

@endforeach