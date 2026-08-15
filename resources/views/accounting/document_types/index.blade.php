<x-app-layout>
    <div class="w-full max-w-5xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Tipos de Documento" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                    @forelse($items as $type)
                        @php $locked = $type->hasIssuedDocuments(); @endphp
                        <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-zertix-secondary rounded-xl flex items-center justify-center text-white shadow-sm flex-shrink-0">
                                        <x-heroicon-s-document-text class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 leading-tight">{{ $type->name }}</h3>
                                        <span class="text-xs font-semibold px-2 py-0.5 bg-zertix-secondary/10 text-zertix-secondary rounded uppercase tracking-wider">
                                            {{ $type->code }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 bg-gray-50 rounded-lg p-3 border border-gray-100">
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase font-bold block tracking-tighter">Prefijo</span>
                                    <p class="text-sm font-mono font-semibold text-gray-700">{{ $type->prefix ?? 'Sin prefijo' }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase font-bold block tracking-tighter">Correlativo Actual</span>
                                    <p class="text-sm font-mono font-semibold text-gray-700">{{ number_format($type->current_number, 0) }}</p>
                                </div>
                            </div>

                            @if($locked)
                                <div class="flex items-center gap-1.5 text-[11px] text-amber-600">
                                    <x-heroicon-s-lock-closed class="w-3.5 h-3.5 flex-shrink-0" />
                                    <span>Correlativo bloqueado: ya existen documentos emitidos con este tipo.</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <span class="text-[11px] text-gray-400">
                                    Actualizado {{ $type->updated_at->diffForHumans() }}
                                </span>

                                @can('edit document types')
                                    <a href="{{ route('configuration.document_types.edit', $type) }}"
                                       title="{{ $locked ? 'El correlativo ya no se puede editar, pero el nombre y el prefijo sí' : 'Nombre, prefijo y correlativo son editables' }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold uppercase transition
                                           {{ $locked ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-zertix-secondary/10 text-zertix-secondary hover:bg-zertix-secondary/20' }}">
                                        @if($locked)
                                            <x-heroicon-s-lock-closed class="w-3.5 h-3.5" /> Editar (correlativo bloqueado)
                                        @else
                                            <x-heroicon-s-pencil class="w-3.5 h-3.5" /> Editar correlativo
                                        @endif
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-16 text-gray-400 bg-gray-50/30 rounded-xl">
                            <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-200 mb-2" />
                            <p>No se encontraron tipos de documentos.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
