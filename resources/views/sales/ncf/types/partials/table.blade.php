{{-- resources/views/sales/ncf/types/partials/table.blade.php --}}
<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $type)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- Nombre del Comprobante --}}
            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm font-bold text-gray-800">
                    {{ $type->name }}
                </td>
            @endif

            {{-- Prefijo (B o E) --}}
            @if(in_array('prefix', $visibleColumns))
                <td class="px-6 py-4 text-sm text-center">
                    <span class="font-mono bg-gray-100 px-2 py-1 rounded border text-gray-600">
                        {{ $type->prefix }}
                    </span>
                </td>
            @endif

            {{-- Código (01, 02, etc) --}}
            @if(in_array('code', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono text-indigo-600 font-bold">
                    {{ $type->code }}
                </td>
            @endif

            {{-- Es Electrónico? --}}
            @if(in_array('is_electronic', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @if($type->is_electronic)
                        <x-ui.badge variant="info" size="sm" icon="heroicon-s-cpu-chip">e-NCF</x-ui.badge>
                    @else
                        <x-ui.badge variant="slate" size="sm" :dot="false">Físico</x-ui.badge>
                    @endif
                </td>
            @endif

            {{-- Requiere RNC --}}
            @if(in_array('requires_rnc', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @if($type->requires_rnc)
                        <span class="text-xs font-medium text-orange-600 flex items-center justify-center">
                            <x-heroicon-s-identification class="w-4 h-4 mr-1" /> Obligatorio
                        </span>
                    @else
                        <span class="text-xs text-gray-400">Opcional</span>
                    @endif
                </td>
            @endif

            {{-- Estado Activo/Inactivo --}}
            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    <x-ui.badge :variant="$type->is_active ? 'success' : 'error'" size="sm">
                        {{ $type->is_active ? 'ACTIVO' : 'INACTIVO' }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Conteo de Secuencias (Usa withCount) --}}
            @if(in_array('sequences_count', $visibleColumns))
                <td class="px-6 py-4 text-center text-sm font-medium text-gray-600">
                    {{ $type->sequences_count ?? 0 }}
                </td>
            @endif

            {{-- Fecha de Creación --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="font-medium">{{ $type->created_at->format('d/m/Y') }}</div>
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button @click="$dispatch('open-modal', 'edit-ncf-type-{{ $type->id }}')" 
                            class="p-2 text-gray-400 hover:text-indigo-600 transition"
                            title="Editar Configuración">
                        <x-heroicon-s-pencil-square class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-12 text-gray-400">
                <div class="flex flex-col items-center">
                    <x-heroicon-o-adjustments-horizontal class="w-12 h-12 mb-2 text-gray-200" />
                    <p>No se han configurado tipos de comprobantes.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('sales.ncf.types.partials.modals')