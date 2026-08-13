<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $type)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            {{-- Columna: Nombre --}}
            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm font-bold text-gray-900">
                    {{ $type->name }}
                </td>
            @endif

            {{-- Columna: Código/Sigla --}}
            @if(in_array('code', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                    <span class="bg-gray-100 px-2 py-0.5 rounded border border-gray-200 uppercase">{{ $type->code }}</span>
                </td>
            @endif

            {{-- Columna: Prefijo --}}
            @if(in_array('prefix', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500 italic">
                    {{ $type->prefix ?? 'Sin prefijo' }}
                </td>
            @endif

            {{-- Columna: Correlativo --}}
            @if(in_array('current_number', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-mono text-gray-600">
                    {{ number_format($type->current_number, 0) }}
                </td>
            @endif

            {{-- Columna: Próximo --}}
            @if(in_array('next_number', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold text-indigo-600">
                    {{ number_format($type->current_number + 1, 0) }}
                </td>
            @endif

            {{-- Estado --}}
            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm
                        {{ $type->is_active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' : 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-600/20' }}">
                        {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
            @endif

            {{-- Modificación --}}
            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $type->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                    <button @click="$dispatch('open-modal', 'view-document-type-{{ $type->id }}')" 
                            class="bg-gray-100 text-gray-600 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm"
                            title="Ver Detalle">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>

                    @can('edit document types')
                        <a href="{{ route('configuration.document_types.edit', $type) }}" 
                           class="text-indigo-600 hover:text-indigo-900 p-2 rounded-full hover:bg-indigo-50"
                           title="Editar">
                            <x-heroicon-s-pencil-square class="w-5 h-5" />
                        </a>
                    @endcan

                    @can('delete document types')
                        <button type="button"
                                @click="$dispatch('open-modal', 'confirm-deletion-{{ $type->id }}')"
                                class="text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50"
                                title="Eliminar">
                            <x-heroicon-s-trash class="w-5 h-5" />
                        </button>
                    @endcan
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p>No se encontraron tipos de documentos.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('accounting.document_types.partials.modals')