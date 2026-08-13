<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $quote)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- 1. ID --}}
            @if(in_array('id', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-indigo-700">
                    #{{ $quote->id }}
                </td>
            @endif

            {{-- 2. Fecha Emisión --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-600">
                    {{ $quote->created_at->format('d/m/Y H:i') }}
                </td>
            @endif

            {{-- 3. Cliente --}}
            @if(in_array('customer_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="font-medium text-gray-900">{{ $quote->customer->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">
                        {{ $quote->customer->tax_id ?? 'Sin RNC/Cédula' }}
                    </div>
                </td>
            @endif

            {{-- 4. Vendedor --}}
            @if(in_array('user_id', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-500">
                    {{ $quote->user->name ?? 'Sistema' }}
                </td>
            @endif

            {{-- 5. Origen --}}
            @if(in_array('origin', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex items-center text-[11px] uppercase font-semibold">
                        @if($quote->origin === \App\Models\Sales\Quotes\Quote::ORIGIN_POS)
                            <x-heroicon-s-computer-desktop class="w-3.5 h-3.5 mr-1.5 text-blue-400" />
                        @else
                            <x-heroicon-s-building-office class="w-3.5 h-3.5 mr-1.5 text-gray-400" />
                        @endif
                        {{ $quote->origin }}
                    </div>
                </td>
            @endif

            {{-- 6. Estado --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @php
                        $sStyles = \App\Models\Sales\Quotes\Quote::getStatusStyles();
                        $sLabels = \App\Models\Sales\Quotes\Quote::getStatuses();
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm border {{ $sStyles[$quote->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $sLabels[$quote->status] ?? $quote->status }}
                    </span>
                </td>
            @endif

            {{-- 7. Monto Total --}}
            @if(in_array('total', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">
                    <span class="text-[10px] font-normal text-gray-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($quote->total, 2) }}
                </td>
            @endif

            {{-- 8. Vencimiento --}}
            @if(in_array('expires_at', $visibleColumns))
                <td class="px-6 py-4 text-xs">
                    @php $isExpired = $quote->expires_at->isPast() && $quote->status !== 'converted'; @endphp
                    <span class="{{ $isExpired ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                        {{ $quote->expires_at->format('d/m/Y') }}
                    </span>
                    @if($isExpired)
                        <span class="block text-[9px] uppercase font-black text-red-400 italic">Expirada</span>
                    @endif
                </td>
            @endif

            {{-- 9. Venta Ref. --}}
            @if(in_array('sale_id', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @if($quote->sale && $quote->sale->invoice)
                        <a href="{{ route('sales.invoices.show', $quote->sale->invoice->id) }}" class="whitespace-nowrap inline-flex items-center px-2 py-1 bg-emerald-50 text-emerald-700 rounded border border-emerald-100 text-[10px] font-bold hover:bg-emerald-100 transition">
                            {{ $quote->sale->number }}
                        </a>
                    @else
                        <span class="text-gray-400 text-xs">N/A</span>
                    @endif
                </td>
            @endif

            {{-- 10. Terminal --}}
            @if(in_array('pos_terminal_id', $visibleColumns))
                <td class="px-6 py-4 text-[10px] text-gray-400">
                    {{ $quote->terminal->name ?? 'N/A' }}
                </td>
            @endif

            {{-- 11. Notas --}}
            @if(in_array('notes', $visibleColumns))
                <td class="px-6 py-4 text-[10px] text-gray-400 max-w-xs truncate">
                    {{ $quote->notes ?? 'N/A' }}
                </td>
            @endif

            {{-- 12. Última Modificación --}}
            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-[10px] text-gray-400">
                    {{ $quote->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- ACCIONES --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                    {{-- Ver --}}
                    <a href="{{ route('sales.quotes.show', $quote) }}" title="Ver Detalle"
                       class="bg-white border border-gray-200 text-gray-500 hover:bg-indigo-600 hover:text-white p-1.5 rounded-lg transition-all">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </a>

                    {{-- Flujo de Estados (Botones Condicionales) --}}
                    @if(!$quote->sale_id && !$quote->expires_at->isPast())
                        
                        {{-- Aprobar (Solo si es Borrador) --}}
                        @if($quote->status === \App\Models\Sales\Quotes\Quote::STATUS_DRAFT)
                            <button @click="$dispatch('open-modal', 'confirm-approve-quote-{{ $quote->id }}')" 
                                    class="bg-white border border-gray-200 text-emerald-600 hover:bg-emerald-600 hover:text-white p-1.5 rounded-lg transition-all" title="Aprobar">
                                <x-heroicon-s-check-circle class="w-4 h-4" />
                            </button>
                        @endif

                        {{-- Convertir (Solo si está Aprobada) --}}
                        @if($quote->status === \App\Models\Sales\Quotes\Quote::STATUS_APPROVED)
                            <button @click="$dispatch('open-modal', 'confirm-convert-quote-{{ $quote->id }}')" 
                                    class="bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-600 hover:text-white p-1.5 rounded-lg transition-all shadow-sm" title="Convertir a Venta">
                                <x-heroicon-s-shopping-cart class="w-4 h-4" />
                            </button>
                        @endif

                        {{-- Cancelar (Si no está convertida ni cancelada) --}}
                        @if($quote->status !== \App\Models\Sales\Quotes\Quote::STATUS_CANCELLED)
                            <button @click="$dispatch('open-modal', 'confirm-cancel-quote-{{ $quote->id }}')" 
                                    class="bg-white border border-gray-200 text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-all" title="Cancelar">
                                <x-heroicon-s-x-circle class="w-4 h-4" />
                            </button>
                        @endif

                        {{-- Editar (Solo si es borrador y no ha expirado) --}}
                        @if($quote->status === \App\Models\Sales\Quotes\Quote::STATUS_DRAFT && !$quote->expires_at->isPast())
                            <a href="{{ route('sales.quotes.edit', $quote) }}" 
                            class="bg-white border border-gray-200 text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all shadow-sm">
                                <x-heroicon-s-pencil-square class="w-4 h-4" />
                            </a>
                        @endif

                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p class="text-sm font-medium">No se encontraron cotizaciones.</p>
                <p class="text-xs">Intenta ajustar los filtros de búsqueda.</p>
            </td>
        </tr>
        @endforelse
</x-data-table>
    
{{-- INCLUSIÓN DE MODALES PARA ESTA FILA --}}
@include('sales.quotes.partials.actions-modals')