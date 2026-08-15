<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $sequence)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- Tipo de Comprobante --}}
            @if(in_array('type_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="font-bold text-indigo-700">{{ $sequence->type->code }}</div>
                    <div class="text-[10px] text-gray-500 uppercase leading-tight">{{ $sequence->type->name }}</div>
                </td>
            @endif

            {{-- Serie --}}
            @if(in_array('series', $visibleColumns))
                <td class="px-6 py-4 text-center font-bold text-gray-700">
                    {{ $sequence->series }}
                </td>
            @endif

            {{-- Rango (Desde - Hasta) --}}
            @if(in_array('range', $visibleColumns))
                <td class="px-6 py-4 text-xs font-mono text-gray-600">
                    <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ str_pad($sequence->from, $sequence->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}</span>
                    <span class="mx-1 text-gray-300">→</span>
                    <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ str_pad($sequence->to, $sequence->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}</span>
                </td>
            @endif

            {{-- Último Usado --}}
            @if(in_array('current', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold">
                    @if($sequence->current >= $sequence->from)
                        <span class="text-indigo-600">{{ $sequence->series }}{{ $sequence->type->code }}{{ str_pad($sequence->current, 8, '0', STR_PAD_LEFT) }}</span>
                    @else
                        <span class="text-gray-300 italic text-[10px]">Sin uso</span>
                    @endif
                </td>
            @endif

            {{-- Disponibles --}}
            @if(in_array('available', $visibleColumns))
                <td class="px-6 py-4 text-sm text-center">
                    @php $available = $sequence->to - $sequence->current; @endphp
                    <span class="font-bold {{ $sequence->isLow() ? 'text-red-600 animate-pulse' : 'text-gray-700' }}">
                        {{ number_format($available) }}
                    </span>
                </td>
            @endif

            {{-- % de Uso --}}
            @if(in_array('usage_percent', $visibleColumns))
                <td class="px-6 py-4">
                    @php 
                        $total = ($sequence->to - $sequence->from + 1);
                        $used = ($sequence->current >= $sequence->from) ? ($sequence->current - $sequence->from + 1) : 0;
                        
                        // Protección contra división por cero
                        $percent = ($total > 0) ? ($used / $total) * 100 : 0;
                        
                        $barColor = $percent > 90 ? 'bg-red-500' : ($percent > 70 ? 'bg-orange-400' : 'bg-green-500');
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-16 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" 
                                style="width: {{ min($percent, 100) }}%"></div>
                        </div>
                        <span class="text-[10px] font-medium text-gray-500">{{ round($percent, 1) }}%</span>
                    </div>
                </td>
            @endif

            {{-- Vencimiento --}}
            @if(in_array('expiry_date', $visibleColumns))
                <td class="px-6 py-4 text-xs">
                    <div class="{{ $sequence->expiry_date->isPast() ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                        {{ $sequence->expiry_date->format('d/m/Y') }}
                    </div>
                    <div class="text-[9px] {{ $sequence->expiry_date->isPast() ? 'text-red-400' : 'text-gray-400' }} uppercase font-medium">
                        @if($sequence->expiry_date->isPast())
                            {{-- Muestra: VENCIDO HACE 1 AÑO, 2 MESES --}}
                            Vencido hace {{ $sequence->expiry_date->diffForHumans(['parts' => 2, 'join' => ', ']) }}
                        @else
                            {{-- Muestra: VENCE EN 10 MESES --}}
                            Vence en {{ $sequence->expiry_date->diffForHumans() }}
                        @endif
                    </div>
                </td>
            @endif

            {{-- Umbral Alerta --}}
            @if(in_array('alert_threshold', $visibleColumns))
                <td class="px-6 py-4 text-xs text-center text-gray-400 font-medium">
                    {{ number_format($sequence->alert_threshold) }}
                </td>
            @endif

            {{-- Estado de la Secuencia (Lote) --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border ring-1 ring-inset shadow-sm {{ $sequence->status_styles }}">
                        <span class="w-1 h-1 rounded-full mr-1.5 bg-current"></span>
                        {{ $sequence->status_label }}
                    </span>
                </td>
            @endif

            {{-- Fecha Registro --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-[10px] text-gray-400">
                    {{ $sequence->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    
                    <button x-on:click="$dispatch('open-modal', 'extend-sequence-{{ $sequence->id }}')" 
                            class="text-green-600 hover:text-green-900 transition" 
                            title="Ampliar Rango">
                        <x-heroicon-s-arrow-trending-up class="w-5 h-5" />
                    </button>

                    {{-- Ver Detalle del Lote --}}
                    <button @click="$dispatch('open-modal', 'view-sequence-{{ $sequence->id }}')" 
                            class="bg-white border border-gray-200 text-gray-500 hover:bg-indigo-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                            title="Ver Detalle y Estadísticas">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>

                    {{-- Eliminar solo si no se ha usado ni un solo número (Lote virgen) --}}
                    @if($sequence->current < $sequence->from)
                        <button @click="$dispatch('open-modal', 'confirm-sequence-deletion-{{ $sequence->id }}')" 
                                class="bg-white border border-gray-200 text-red-500 hover:bg-red-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                                title="Eliminar Lote">
                            <x-heroicon-s-trash class="w-4 h-4" />
                        </button>
                        
                        {{-- Modal de Confirmación de Deletción (Integrado aquí o en partial de modales) --}}
                        <x-modal name="confirm-sequence-deletion-{{ $sequence->id }}" focusable>
                            <form method="post" action="{{ route('finance.ncf.sequences.destroy', $sequence) }}" class="p-6 text-left">
                                @csrf
                                @method('DELETE')
                                <h2 class="text-lg font-medium text-gray-900">
                                    ¿Está seguro de que desea eliminar este lote de NCF?
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Esta acción no se puede deshacer. Se eliminará la secuencia <strong>{{ $sequence->series }}{{ $sequence->type->code }}</strong> desde el {{ $sequence->from }} hasta el {{ $sequence->to }}.
                                </p>
                                <div class="mt-6 flex justify-end">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                                    <x-danger-button class="ml-3">Eliminar Lote</x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    @else
                        {{-- Candado si ya hay uso: Indica integridad de datos DGII --}}
                        <button class="bg-gray-50 border border-gray-100 text-gray-300 p-2 rounded-lg cursor-not-allowed" 
                                title="No se puede eliminar: Este lote ya tiene comprobantes emitidos (Auditoría DGII)">
                            <x-heroicon-s-lock-closed class="w-4 h-4" />
                        </button>
                    @endif

                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-document-duplicate class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p class="text-sm">No hay secuencias configuradas para comprobantes fiscales.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>
@include('sales.ncf.sequences.partials.modals')