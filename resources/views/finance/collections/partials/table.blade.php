<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $payment)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- Fecha de Pago --}}
            @if(in_array('payment_date', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <span class="block font-medium text-gray-700">{{ $payment->payment_date->format('d/m/Y') }}</span>
                    <span class="text-[10px]">{{ $payment->created_at->format('h:i A') ?? 'No registrada' }}</span>
                </td>
            @endif

            {{-- No. Recibo --}}
            @if(in_array('receipt_number', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-indigo-700">
                    {{ $payment->receipt_number }}
                </td>
            @endif

            {{-- Cliente --}}
            @if(in_array('client', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="font-medium text-gray-900">{{ $payment->client->name }}</div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ $payment->client->tax_id ?? 'Sin RNC/Cédula' }}</div>
                </td>
            @endif

            {{-- Factura/Doc Aplicado --}}
            @if(in_array('receivable', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    @if($payment->receivable)
                        <span class="text-gray-700 font-medium">{{ $payment->receivable->document_number }}</span>
                    @else
                        <span class="text-gray-400 italic">Anticipo / General</span>
                    @endif
                </td>
            @endif

            {{-- Método de Pago --}}
            @if(in_array('tipo_pago', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    @php
                        $tpHex = \App\Models\Configuration\TipoPago::getBadgeHexColors()[$payment->tipoPago->slug ?? null]
                            ?? \App\Models\Configuration\TipoPago::getDefaultBadgeHex();
                        $tpIcon = \App\Models\Configuration\TipoPago::getBadgeIcons()[$payment->tipoPago->slug ?? null]
                            ?? \App\Models\Configuration\TipoPago::getDefaultBadgeIcon();
                    @endphp
                    <x-ui.badge :hex="$tpHex" :icon="$tpIcon" size="sm">
                        {{ $payment->tipoPago->nombre ?? 'N/A' }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Referencia --}}
            @if(in_array('reference', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500 max-w-[150px] truncate" title="{{ $payment->reference }}">
                    {{ $payment->reference ?? '---' }}
                </td>
            @endif

            {{-- Monto Pagado --}}
            @if(in_array('amount', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">
                    {{ number_format($payment->amount, 2) }}
                </td>
            @endif

            {{-- Estado --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @php
                        $statusVariant = match($payment->status) {
                            \App\Models\Accounting\ClientCollection::STATUS_ACTIVE => 'success',
                            \App\Models\Accounting\ClientCollection::STATUS_CANCELLED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$statusVariant" size="sm" :dot="false">
                        {{ $payment->status_label }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Registrado por --}}
            @if(in_array('created_by', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    {{ $payment->creator->name ?? 'Sistema' }}
                </td>
            @endif

            {{-- Fecha Registro --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $payment->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    {{-- Ver Detalle del Cobro --}}
                    <button @click="$dispatch('open-modal', 'view-payment-{{ $payment->id }}')" 
                            class="bg-white border border-gray-200 text-gray-500 hover:bg-indigo-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                            title="Ver Recibo">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>

                    {{-- Imprimir Comprobante --}}
                    <a href="{{ route('finance.collections.print', $payment->id) }}"
                    target="_blank" 
                    class="bg-white border border-gray-200 text-gray-500 hover:bg-gray-800 hover:text-white p-2 rounded-lg transition-all shadow-sm" 
                    title="Imprimir Recibo">
                        <x-heroicon-s-printer class="w-4 h-4" />
                    </a>

                    {{-- Anular Cobro (Solo si está activo) --}}
                    @can('cancel payments')
                        @if($payment->status === \App\Models\Accounting\ClientCollection::STATUS_ACTIVE)
                            <button @click="$dispatch('open-modal', 'confirm-cancel-payment-{{ $payment->id }}')"
                                    class="bg-white border border-gray-200 text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all shadow-sm"
                                    title="Anular Cobro">
                                <x-heroicon-s-x-circle class="w-4 h-4" />
                            </button>
                        @endif
                    @endcan
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p>No se encontraron registros de cobros.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>

{{-- Incluiremos los modales en el siguiente paso --}}
@include('finance.collections.partials.modals')