<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $movement)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            {{-- ID --}}
            @if(in_array('id', $visibleColumns))
                <td class="px-6 py-4 text-xs font-mono text-gray-400">
                    #{{ $movement->id }}
                </td>
            @endif

            {{-- Fecha y Hora --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <span class="block font-medium text-gray-700">{{ $movement->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px]">{{ $movement->created_at->format('h:i A') }}</span>
                </td>
            @endif

            {{-- Sesión --}}
            @if(in_array('pos_session_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex flex-col">
                        <span class="font-medium text-indigo-600">SES-{{ $movement->pos_session_id }}</span>
                        <span class="text-[10px] text-gray-400">{{ $movement->session->terminal->name ?? 'N/A' }}</span>
                    </div>
                </td>
            @endif

            {{-- Usuario / Cajero --}}
            @if(in_array('user_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-700">
                    <div class="flex items-center">
                        <div class="h-7 w-7 rounded-full bg-gray-100 flex items-center justify-center mr-2 text-[10px] font-bold text-gray-500">
                            {{ substr($movement->user->name, 0, 2) }}
                        </div>
                        {{ $movement->user->name }}
                    </div>
                </td>
            @endif

            {{-- Asiento Contable --}}
            @if(in_array('accounting_entry_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    @if($movement->accounting_entry_id)
                        <div class="flex items-center text-blue-600 font-medium hover:underline cursor-pointer">
                            <x-heroicon-s-document-text class="w-4 h-4 mr-1 opacity-70" />
                            #{{ $movement->accounting_entry_id }}
                        </div>
                    @else
                        <span class="text-gray-300 italic text-[10px]">No generado</span>
                    @endif
                </td>
            @endif

            @if(in_array('accounting_account_id', $visibleColumns))
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col">
                        @if($movement->account)
                            <span class="text-sm font-bold text-gray-800">
                                {{ $movement->account->name }}
                            </span>
                            <span class="text-[10px] font-mono text-gray-400">
                                {{ $movement->account->code }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400 italic">No asignada</span>
                        @endif
                    </div>
                </td>
            @endif

            {{-- Tipo de Movimiento con Estilos del Modelo --}}
            @if(in_array('type', $visibleColumns))
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $style = \App\Models\Sales\Pos\PosCashMovement::getTypeStyles()[$movement->type] ?? 'bg-gray-100 text-gray-800';
                        $icon = \App\Models\Sales\Pos\PosCashMovement::getTypeIcons()[$movement->type] ?? 'heroicon-s-minus';
                        $label = \App\Models\Sales\Pos\PosCashMovement::getTypes()[$movement->type] ?? $movement->type;
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $style }}">
                        <x-dynamic-component :component="$icon" class="w-3 h-3 mr-1" />
                        {{ $label }}
                    </span>
                </td>
            @endif

            {{-- Monto --}}
            @if(in_array('amount', $visibleColumns))
                <td class="px-6 py-4 text-sm font-bold whitespace-nowrap">
                    <span class="{{ $movement->type === 'in' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ $movement->type === 'in' ? '+' : '-' }} ${{ number_format($movement->amount, 2) }}
                    </span>
                </td>
            @endif

            {{-- Motivo / Razón --}}
            @if(in_array('reason', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                    {{ $movement->reason }}
                </td>
            @endif

            {{-- Referencia --}}
            @if(in_array('reference', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500 italic">
                    {{ $movement->reference ?? 'Sin referencia' }}
                </td>
            @endif

            {{-- Metadatos (Mini Indicador) --}}
            @if(in_array('metadata', $visibleColumns))
                <td class="px-6 py-4">
                    @if(!empty($movement->metadata))
                        <span class="text-gray-400" title="Contiene información extra">
                            <x-heroicon-s-variable class="w-4 h-4" />
                        </span>
                    @else
                        -
                    @endif
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                    <button @click="$dispatch('open-modal', 'view-movement-{{ $movement->id }}')" 
                            class="bg-gray-50 text-gray-500 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm"
                            title="Ver detalles del movimiento">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-12">
                <div class="flex flex-col items-center justify-center text-gray-400">
                    <x-heroicon-s-circle-stack class="w-12 h-12 mb-2 opacity-20" />
                    <p class="italic">No se encontraron movimientos con los filtros seleccionados.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>