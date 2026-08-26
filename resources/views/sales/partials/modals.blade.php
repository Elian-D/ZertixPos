@foreach($items as $sale)
{{-- 1. MODAL: VISTA DE DETALLE DE VENTA --}}
<x-modal name="view-sale-{{ $sale->id }}" maxWidth="2xl">
    <div class="overflow-hidden rounded-xl bg-white shadow-2xl">
        {{-- Header Dinámico --}}
        @php
            $statusLabels = \App\Models\Sales\Sale::getStatuses();
            $paymentLabels = \App\Models\Sales\Sale::getPaymentTypes();
            $statusVariant = match($sale->status) {
                \App\Models\Sales\Sale::STATUS_COMPLETED => 'success',
                \App\Models\Sales\Sale::STATUS_CANCELED => 'error',
                default => 'slate',
            };
        @endphp

        <div class="bg-gray-50 px-6 md:px-8 py-6 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-black text-gray-900 tracking-tight">Detalle de Venta</h3>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                    <span class="text-xs font-mono text-zertix-primary-600 bg-zertix-primary-50 px-2 py-0.5 rounded border border-zertix-primary-100">
                        {{ $sale->number }}
                    </span>
                    @if($sale->pos_terminal_id)
                        <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 uppercase">
                            {{ $sale->posTerminal->name }}
                        </span>
                    @endif
                </div>
            </div>

            <x-ui.badge :variant="$statusVariant" :dot="false">
                {{ strtoupper($statusLabels[$sale->status] ?? $sale->status) }}
            </x-ui.badge>
        </div>

        {{-- Alerta de Anulación --}}
        @if($sale->status === 'canceled')
            <div class="bg-red-50 px-8 py-2 border-b border-red-100 flex items-center gap-2">
                <x-heroicon-s-information-circle class="w-4 h-4 text-red-500"/>
                <span class="text-[10px] font-bold text-red-700 uppercase">
                    Anulada: {{ $sale->ncfLog->cancellation_reason ?? 'No especificado' }}
                </span>
            </div>
        @endif

        <div class="p-6 md:p-8">
            {{-- Info Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8">
                {{-- Cliente --}}
                <div class="flex gap-3 items-start">
                    <div class="w-8 h-8 bg-zertix-primary-50 rounded-lg flex items-center justify-center text-zertix-primary-600 shrink-0">
                        <x-heroicon-s-user class="w-4 h-4"/>
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Cliente</span>
                        <p class="text-sm font-bold text-gray-800 truncate">{{ $sale->client->name ?? 'Consumidor Final' }}</p>
                        <p class="text-[10px] text-gray-500">{{ $sale->client->tax_id ?? '' }}</p>
                    </div>
                </div>

                {{-- Origen / POS --}}
                <div class="flex gap-3 items-start">
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 shrink-0">
                        <x-heroicon-s-computer-desktop class="w-4 h-4"/>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Origen / Sesión</span>
                        <p class="text-sm font-semibold text-gray-700">{{ $sale->pos_terminal_id ? $sale->posTerminal->name : 'Administración' }}</p>
                        <p class="text-[10px] text-gray-500">Sesión: {{ $sale->pos_session_id ?? 'N/A' }}</p>
                    </div>
                </div>

                {{-- Fecha y Cajero --}}
                <div class="flex gap-3 items-start">
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 shrink-0">
                        <x-heroicon-s-calendar class="w-4 h-4"/>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Fecha / Atendido</span>
                        <p class="text-sm font-semibold text-gray-700">{{ $sale->sale_date->format('d/m/Y') }}</p>
                        <p class="text-[10px] text-gray-500">{{ $sale->user->name }}</p>
                    </div>
                </div>
            </div>

            {{-- TABLA DE ARTÍCULOS --}}
            <div class="border rounded-xl overflow-hidden mb-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-black uppercase text-gray-400">Producto</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase text-gray-400 text-center">Cant.</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase text-gray-400 text-right">Precio Bruto</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase text-gray-400 text-right">Descuento</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase text-gray-400 text-right">Total Línea</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($sale->items as $item)
                                @php
                                    // Cálculo de respaldo para evitar inconsistencias visuales
                                    $itemBruto = $item->quantity * $item->unit_price;
                                    $itemDescuento = $item->discount_amount ?? 0;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 leading-tight">{{ $item->product->name ?? 'P. Eliminado' }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $item->product->sku ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-600">
                                        {{ number_format($item->quantity, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500 text-xs">
                                        {{ config('regional.currency_symbol') }}{{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-amber-600">
                                        @if($itemBruto > 0 && $itemDescuento > 0)
                                            <span class="text-[10px] bg-amber-50 px-1 py-0.5 rounded font-semibold mr-1">
                                                {{ number_format(($itemDescuento / $itemBruto) * 100, 0) }}%
                                            </span>
                                            -{{ config('regional.currency_symbol') }}{{ number_format($itemDescuento, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900">
                                        {{-- El subtotal del item ya almacena el valor neto post-descuento --}}
                                        {{ config('regional.currency_symbol') }}{{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DESGLOSE DE PAGOS MULTIPLES --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start mb-6">
                {{-- Columna Izquierda: Notas --}}
                <div>
                    @if($sale->notes)
                        <div class="bg-amber-50 p-3 rounded-lg border border-dashed border-amber-200">
                            <span class="text-[9px] font-bold text-amber-500 uppercase tracking-widest block mb-1">Observaciones</span>
                            <p class="text-xs text-amber-800 italic leading-relaxed">"{{ $sale->notes }}"</p>
                        </div>
                    @endif
                </div>

                {{-- Columna Derecha: Totales y Pagos --}}
                <div class="space-y-3">
                    @php
                        // Desglose real por tipo de impuesto (Fase 5, REQ-5.6) — agrupa el
                        // snapshot congelado de cada línea, ya no una tasa global fija.
                        $taxBreakdown = $sale->items->pluck('tax_breakdown')->filter()->flatten(1)->groupBy('key');
                    @endphp

                    {{-- Subtotal Bruto Real --}}
                    <div class="flex justify-between text-xs text-gray-500 px-1">
                        <span>Subtotal Bruto</span>
                        <span class="font-mono">{{ config('regional.currency_symbol') }}{{ number_format($sale->total_amount, 2) }}</span>
                    </div>

                    {{-- Descuentos Aplicados --}}
                    @if($sale->discount_total > 0)
                        <div class="flex justify-between text-xs text-amber-600 px-1">
                            <span class="flex items-center gap-1">
                                <x-heroicon-s-tag class="w-3 h-3"/>
                                Total Descuentos
                            </span>
                            <span class="font-mono font-bold">-{{ config('regional.currency_symbol') }}{{ number_format($sale->discount_total, 2) }}</span>
                        </div>
                        
                        {{-- Subtotal Neto --}}
                        <div class="flex justify-between text-xs font-semibold text-gray-700 px-1 pb-1 border-b border-gray-100">
                            <span>Subtotal Neto</span>
                            <span class="font-mono">{{ config('regional.currency_symbol') }}{{ number_format($sale->net_amount, 2) }}</span>
                        </div>
                    @endif

                    {{-- Impuestos (desglose real por tipo, Fase 5 REQ-5.6) --}}
                    @forelse($taxBreakdown as $key => $lines)
                        <div class="flex justify-between text-xs text-gray-500 px-1">
                            <span>{{ $lines->first()['label'] }}</span>
                            <span class="font-mono">{{ config('regional.currency_symbol') }}{{ number_format($lines->sum('amount'), 2) }}</span>
                        </div>
                    @empty
                        <div class="flex justify-between text-xs text-gray-500 px-1">
                            <span>Impuestos (Exento)</span>
                            <span class="font-mono">{{ config('regional.currency_symbol') }}0.00</span>
                        </div>
                    @endforelse

                    {{-- Total Final --}}
                    <div class="flex justify-between items-center bg-zertix-primary-50 p-3 rounded-lg border border-zertix-primary-100">
                        <span class="text-xs font-black text-zertix-primary-700 uppercase">Total Facturado</span>
                        <span class="text-xl font-black text-zertix-primary-700 font-mono">{{ config('regional.currency_symbol') }}{{ number_format($sale->grand_total, 2) }}</span>
                    </div>

                    {{-- Distribución de Métodos Usados --}}
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2 text-right">Distribución del Pago</span>
                        @forelse($sale->payments as $payment)
                            <div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-0">
                                <span class="text-xs text-gray-600 flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                                    <span class="font-medium">{{ $payment->tipoPago->nombre ?? 'Pago Registrado' }}</span>
                                    @if($payment->reference) 
                                        <span class="text-[9px] text-gray-400">({{ $payment->reference }})</span> 
                                    @endif
                                </span>
                                <span class="text-sm font-bold text-gray-700 font-mono">{{ config('regional.currency_symbol') }}{{ number_format($payment->amount, 2) }}</span>
                            </div>
                        @empty
                            <div class="text-right text-[11px] text-gray-400 italic">
                                Ventas en cuenta corriente / Crédito comercial
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Acciones --}}
        <div class="px-8 py-5 bg-gray-50 border-t flex flex-col md:flex-row justify-between items-center gap-4">
            <span class="text-[10px] text-gray-400 italic">Creado el {{ $sale->created_at->format('d/m/Y H:i') }}</span>
            <div class="flex gap-3 w-full md:w-auto">
                <x-ui.button appearance="ghost" variant="secondary" class="flex-1 md:flex-none justify-center" x-on:click="$dispatch('close')">Cerrar</x-ui.button>
                <a href="{{ route('sales.print-invoice', $sale) }}" target="_blank" 
                   class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 bg-gray-800 hover:bg-black border border-transparent rounded-md font-bold text-[10px] text-white uppercase tracking-widest transition shadow-md">
                    <x-heroicon-s-printer class="w-3.5 h-3.5 mr-2"/> Reimprimir Ticket
                </a>
            </div>
        </div>
    </div>
</x-modal>
    
{{-- 2. MODAL: CONFIRMACIÓN DE ANULACIÓN --}}
<x-modal name="confirm-cancel-sale-{{ $sale->id }}" maxWidth="sm">
    <form action="{{ route('sales.cancel', $sale) }}" method="POST" class="p-6">
        @csrf
        @method('PATCH')
        
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <x-heroicon-s-exclamation-triangle class="w-10 h-10"/>
        </div>
        
        <div class="text-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">¿Anular Venta?</h3>
            <p class="text-xs text-gray-500 mt-1">
                Venta: <strong>{{ $sale->number }}</strong>
            </p>
        </div>

        {{-- Campo de Motivo --}}
        <div class="mt-4 text-left">
            <x-ui.forms.select label="Motivo de Anulación (DGII)" name="cancellation_reason" required
                hint="Requerido por la DGII para el reporte de anulación del NCF">
                <option value="01 - ERRORES DE DIGITACION">01 - Errores de digitación</option>
                <option value="02 - ERRORES DE IMPRESION">02 - Errores de impresión</option>
                <option value="03 - PRODUCTO DEFECTUOSO">03 - Producto defectuoso</option>
                <option value="04 - DEVOLUCION">04 - Devolución</option>
                <option value="05 - OTROS">05 - Otros</option>
            </x-ui.forms.select>
        </div>

        <div class="mt-8 flex justify-center gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Volver</x-ui.button>
            <button type="submit" class="px-6 py-2 bg-red-600 text-white text-xs font-bold uppercase rounded-lg hover:bg-red-700 transition-colors shadow-lg shadow-red-200">
                Confirmar Anulación
            </button>
        </div>
    </form>
</x-modal>
@endforeach