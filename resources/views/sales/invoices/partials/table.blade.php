<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $invoice)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- N° Factura --}}
            @if(in_array('invoice_number', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-gray-900">
                    <div class="flex items-center">
                        <x-heroicon-s-document-check class="w-4 h-4 mr-2 text-emerald-600" />
                        {{ $invoice->invoice_number }}
                    </div>
                </td>
            @endif

            {{-- Venta Origen --}}
            @if(in_array('sale_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <span class="text-zertix-primary-600 font-medium italic">#{{ $invoice->sale->number ?? 'N/A' }}</span>
                </td>
            @endif

            {{-- Cliente --}}
            @if(in_array('client_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="font-medium text-gray-900">{{ $invoice->sale->client->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">ID: {{ $invoice->sale->client_id }}</div>
                </td>
            @endif

            {{-- Tipo Venta (Badge Dinámico de Sale) --}}
            @if(in_array('type', $visibleColumns))
                <td class="px-6 py-4">
                    @php
                        $pLabels = \App\Models\Sales\Sale::getPaymentTypes();
                        $pVariant = match($invoice->type) {
                            \App\Models\Sales\Sale::PAYMENT_CASH => 'info',
                            \App\Models\Sales\Sale::PAYMENT_CREDIT => 'warning',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$pVariant" size="sm" :dot="false">
                        {{ $pLabels[$invoice->type] ?? $invoice->type }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Formato (Icono + Label) --}}
            @if(in_array('format_type', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    @php
                        $fIcons = \App\Models\Sales\Invoice::getFormatIcons();
                        $fLabels = \App\Models\Sales\Invoice::getFormats();
                    @endphp
                    <div class="flex items-center">
                        <x-dynamic-component :component="$fIcons[$invoice->format_type] ?? 'heroicon-s-question-mark-circle'" class="w-4 h-4 mr-1.5 text-gray-400" />
                        {{ $fLabels[$invoice->format_type] ?? $invoice->format_type }}
                    </div>
                </td>
            @endif

            {{-- Monto Total — grand_total (neto + impuesto), no total_amount (bruto sin
                 impuesto; ni siquiera consistente entre sí según el origen de la venta,
                 ver QuoteService::convertToSale()) — Fase 5, auditoría post-REQ-5.12. --}}
            @if(in_array('total_amount', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">
                    <span class="text-[10px] font-normal text-gray-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($invoice->sale->grand_total ?? 0, 2) }}
                </td>
            @endif

            {{-- Estado Documento (Usando tus estilos de Invoice) --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @php
                        $sLabels = \App\Models\Sales\Invoice::getStatuses();
                        $sVariant = match($invoice->status) {
                            \App\Models\Sales\Invoice::STATUS_ACTIVE => 'success',
                            \App\Models\Sales\Invoice::STATUS_CANCELLED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$sVariant" size="sm" :dot="false">
                        {{ $sLabels[$invoice->status] ?? $invoice->status }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Vencimiento --}}
            @if(in_array('due_date', $visibleColumns))
                <td class="px-6 py-4 text-[11px] {{ $invoice->due_date && $invoice->due_date->isPast() && $invoice->status === 'active' ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                    {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                </td>
            @endif

            {{-- Emitido por --}}
            @if(in_array('generated_by', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    {{ $invoice->generated_by }}
                </td>
            @endif

            {{-- Fecha Emisión --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <span class="block font-medium">{{ $invoice->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-gray-400">{{ $invoice->created_at->format('h:i A') }}</span>
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    {{-- Ver PDF/Vista Previa --}}
                    <a href="{{ route('finance.invoices.show', $invoice) }}" 
                       class="bg-white border border-gray-200 text-gray-500 hover:bg-zertix-primary-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                       title="Ver Factura">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </a>

                    {{-- Re-Imprimir --}}
                    <a href="{{ route('finance.invoices.print', $invoice) }}" target="_blank" 
                       class="bg-white border border-gray-200 text-gray-500 hover:bg-gray-800 hover:text-white p-2 rounded-lg transition-all shadow-sm" 
                       title="Imprimir Comprobante">
                        <x-heroicon-s-printer class="w-4 h-4" />
                    </a>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p class="text-sm">No se encontraron facturas emitidas.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>