<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $log)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- NCF Completo --}}
            @if(in_array('full_ncf', $visibleColumns))
                <td class="px-6 py-4 font-mono font-bold text-zertix-primary-700">
                    {{ $log->full_ncf }}
                </td>
            @endif

            {{-- Tipo --}}
            @if(in_array('type_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <span class="text-gray-700 font-medium">{{ $log->type->code }}</span>
                    <span class="text-[10px] text-gray-400 block">{{ $log->type->name }}</span>
                </td>
            @endif

            {{-- Venta Vinculada --}}
            @if(in_array('sale_number', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <a href="{{ route('sales.index', $log->sale_id) }}" class="text-zertix-primary-600 hover:underline font-medium">
                        #{{ $log->sale->number }}
                    </a>
                </td>
            @endif

            {{-- Cliente (Nombre) --}}
            @if(in_array('customer', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-700 font-medium">
                    {{ $log->sale->client->name ?? 'Consumidor Final' }}
                </td>
            @endif

            {{-- RNC/Cédula --}}
            @if(in_array('customer_rnc', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono text-gray-600">
                    {{ $log->sale->client->tax_id ?? 'N/A' }}
                </td>
            @endif

            {{-- Estado --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @php
                        $statusVariants = [
                            \App\Models\Sales\Ncf\NcfLog::STATUS_USED   => 'info',
                            \App\Models\Sales\Ncf\NcfLog::STATUS_VOIDED => 'slate',
                        ];
                        $statusLabels = \App\Models\Sales\Ncf\NcfLog::getStatuses();
                    @endphp
                    <x-ui.badge :variant="$statusVariants[$log->status] ?? 'slate'" size="sm" :dot="false">
                        {{ strtoupper($statusLabels[$log->status] ?? $log->status) }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Motivo de Anulación --}}
            @if(in_array('cancellation_reason', $visibleColumns))
                <td class="px-6 py-4 text-sm text-red-500 italic">
                    {{ $log->cancellation_reason ?? '-' }}
                </td>
            @endif

            {{-- Usuario --}}
            @if(in_array('user_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $log->user->name ?? 'Sist.' }}
                </td>
            @endif

            {{-- Fecha de Uso --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="font-medium">{{ $log->created_at->format('d/m/Y') }}</div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ $log->created_at->format('h:i:s A') }}</div>
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button @click="$dispatch('open-modal', 'view-log-{{ $log->id }}')" 
                            class="p-2 text-gray-400 hover:text-zertix-primary-600 transition">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-12 text-gray-400">
                <div class="flex flex-col items-center">
                    <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mb-2 text-gray-200" />
                    <p>No se encontraron registros de NCF con los filtros aplicados.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('sales.ncf.logs.partials.modals')