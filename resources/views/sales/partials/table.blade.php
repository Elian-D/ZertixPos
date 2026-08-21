<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $sale)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- Fecha de Venta --}}
            @if(in_array('sale_date', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <span class="block font-medium text-gray-700">{{ $sale->sale_date->format('d/m/Y') }}</span>
                    <span class="text-[10px]">{{ $sale->sale_date->format('h:i A') }}</span>
                </td>
            @endif

            {{-- Folio / Número --}}
            @if(in_array('number', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-indigo-700">
                    {{ $sale->number }}
                </td>
            @endif

            {{-- Cliente --}}
            @if(in_array('client_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="font-medium text-gray-900">{{ $sale->client->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ $sale->client->tax_id ?? 'Consumidor Final' }}</div>
                </td>
            @endif

            {{-- Almacén --}}
            @if(in_array('warehouse_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex items-center">
                        <x-heroicon-s-building-storefront class="w-3.5 h-3.5 mr-1.5 text-gray-400" />
                        {{ $sale->warehouse->name ?? 'N/A' }}
                    </div>
                </td>
            @endif

            {{-- Terminal POS --}}
            @if(in_array('pos_terminal_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    @if($sale->pos_terminal_id)
                        <div class="flex items-center">
                            <div class="p-1 bg-blue-50 rounded mr-2">
                                <x-heroicon-s-computer-desktop class="w-3.5 h-3.5 text-blue-500" />
                            </div>
                            <span class="font-medium text-gray-700">{{ $sale->posTerminal->name }}</span>
                        </div>
                    @else
                        <span class="text-gray-400 italic text-xs italic">Ventanilla/Admin</span>
                    @endif
                </td>
            @endif

            {{-- Sesión POS --}}
            @if(in_array('pos_session_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600 text-center">
                    @if($sale->pos_session_id)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 text-[10px] font-mono font-bold ring-1 ring-inset ring-gray-200">
                            #{{ $sale->pos_session_id }}
                        </span>
                    @else
                        <span class="text-gray-300">-</span>
                    @endif
                </td>
            @endif


            {{-- Tipo de Pago --}}
            @if(in_array('payment_type', $visibleColumns))
                <td class="px-6 py-4">
                    @php
                        $pIcons  = \App\Models\Sales\Sale::getPaymentTypeIcons();
                        $pLabels = \App\Models\Sales\Sale::getPaymentTypes();
                        $currentIcon  = $pIcons[$sale->payment_type] ?? 'heroicon-s-question-mark-circle';
                        $currentVariant = match($sale->payment_type) {
                            \App\Models\Sales\Sale::PAYMENT_CASH => 'info',
                            \App\Models\Sales\Sale::PAYMENT_CREDIT => 'warning',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$currentVariant" :icon="$currentIcon" size="sm">
                        {{ $pLabels[$sale->payment_type] ?? $sale->payment_type }}
                    </x-ui.badge>
                </td>
            @endif
            
            {{-- Método de Pago Detallado --}}
            @if(in_array('tipo_pago_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    @php
                        // No usamos $sale->tipoPago->nombre: ese campo es sales.tipo_pago_id,
                        // el "principal" heredado de antes de existir pago dividido. Una venta
                        // mixta (varias filas en sale_payments) mostraba solo ese método suelto
                        // en vez de "Mixto" — misma lógica que ya usa el reporte de turno POS.
                        $isCredit = $sale->payment_type === \App\Models\Sales\Sale::PAYMENT_CREDIT;
                        $isMixed = !$isCredit && $sale->payments->count() > 1;
                        $singlePayment = (!$isCredit && !$isMixed) ? $sale->payments->first()?->tipoPago : null;

                        $paymentBadgeKey = $isCredit
                            ? \App\Models\Configuration\TipoPago::CREDITO
                            : ($isMixed
                                ? \App\Models\Configuration\TipoPago::MIXTO
                                : ($singlePayment->slug ?? null));

                        $paymentLabel = $isCredit ? 'Crédito' : ($isMixed ? 'Mixto' : ($singlePayment->nombre ?? 'N/A'));

                        $pmHex   = \App\Models\Configuration\TipoPago::getBadgeHexColors();
                        $pmIcons = \App\Models\Configuration\TipoPago::getBadgeIcons();
                        $paymentBadgeHex  = $pmHex[$paymentBadgeKey] ?? \App\Models\Configuration\TipoPago::getDefaultBadgeHex();
                        $paymentBadgeIcon = $pmIcons[$paymentBadgeKey] ?? \App\Models\Configuration\TipoPago::getDefaultBadgeIcon();
                    @endphp
                    <x-ui.badge :hex="$paymentBadgeHex" :icon="$paymentBadgeIcon" size="sm">
                        {{ $paymentLabel }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Total Venta — grand_total (neto + impuesto), no total_amount (bruto,
                 ver auditoría post-REQ-5.12 en docs/features/v1.2.0.md). --}}
            @if(in_array('total_amount', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">
                    <span class="text-[10px] font-normal text-gray-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($sale->grand_total, 2) }}
                </td>
            @endif

            {{-- Estado --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @php
                        $sLabels = \App\Models\Sales\Sale::getStatuses();
                        $sVariant = match($sale->status) {
                            \App\Models\Sales\Sale::STATUS_COMPLETED => 'success',
                            \App\Models\Sales\Sale::STATUS_CANCELED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$sVariant" size="sm" :dot="false">
                        {{ $sLabels[$sale->status] ?? $sale->status }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Vendedor --}}
            @if(in_array('user_id', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <div class="flex items-center">
                        {{ $sale->user->name ?? 'Sistema' }}
                    </div>
                </td>
            @endif

            {{-- Notas --}}
            @if(in_array('notes', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400 max-w-[150px] truncate italic" title="{{ $sale->notes }}">
                    {{ $sale->notes ?? 'Sin observaciones' }}
                </td>
            @endif

            {{-- Fecha Registro --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $sale->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            {{-- Última Actualización --}}
            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $sale->updated_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    {{-- Ver Detalle --}}
                    <button @click="$dispatch('open-modal', 'view-sale-{{ $sale->id }}')" 
                            class="bg-white border border-gray-200 text-gray-500 hover:bg-indigo-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                            title="Ver Detalle de Venta">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>

                    {{-- Imprimir Factura/Ticket --}}
                    <a href="{{ route('sales.print-invoice', $sale) }}" target="_blank" 
                    class="bg-white border border-gray-200 text-gray-500 hover:bg-gray-800 hover:text-white p-2 rounded-lg transition-all shadow-sm" 
                    title="Imprimir Comprobante">
                        <x-heroicon-s-printer class="w-4 h-4" />
                    </a>

                    {{-- Anular Venta (Solo si está completada) --}}
                    @can('cancel sales')
                        @if($sale->status === \App\Models\Sales\Sale::STATUS_COMPLETED)
                            <button @click="$dispatch('open-modal', 'confirm-cancel-sale-{{ $sale->id }}')" 
                                    class="bg-white border border-gray-200 text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all shadow-sm"
                                    title="Anular Venta">
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
                <x-heroicon-o-shopping-cart class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p class="text-sm">No se encontraron registros de ventas.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('sales.partials.modals')