<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex-1">
                    <a href="{{ route('sales.quotes.index') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-semibold transition mb-3">
                        <x-heroicon-s-arrow-left class="w-4 h-4 mr-1.5" />
                        Regresar al Historial
                    </a>
                    <div class="flex items-center gap-3">
                        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                            Cotizacion <span class="text-amber-600">#{{ str_pad($quote->id, 8, '0', STR_PAD_LEFT) }}</span>
                        </h2>
                        <x-ui.badge :variant="match($quote->status) {
                                'draft' => 'slate',
                                'approved' => 'success',
                                'converted' => 'info',
                                'expired' => 'error',
                                default => 'slate',
                            }" :dot="false">
                            {{
                                $quote->status === 'draft' ? 'Borrador'
                                : ($quote->status === 'approved' ? 'Aprobada'
                                : ($quote->status === 'converted' ? 'Convertida'
                                : ($quote->status === 'expired' ? 'Expirada'
                                : ($quote->status === 'cancelled' ? 'Cancelada' : ucfirst($quote->status)))))
                            }}
                        </x-ui.badge>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-ui.forms.select id="formatSelector" placeholder="">
                        <option value="letter">Carta (PDF)</option>
                        <option value="ticket">Ticket (Termica)</option>
                    </x-ui.forms.select>

                    <a id="printBtn" 
                       href="{{ route('sales.quotes.print', ['quote' => $quote, 'format' => 'letter']) }}" 
                       target="_blank" 
                       class="inline-flex items-center px-5 py-2.5 bg-gray-900 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-gray-800 shadow-lg active:scale-95 transition-all">
                        <x-heroicon-s-printer class="w-4 h-4 mr-2" />
                        Imprimir
                    </a>
                    
                    <a id="downloadBtn"
                       href="{{ route('sales.quotes.print', ['quote' => $quote, 'format' => 'letter']) }}?download=1" 
                       class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg font-bold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 active:scale-95 transition-all"
                       target="blank">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4 mr-2" />
                        Descargar PDF
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                
                <div class="xl:col-span-1 space-y-6">

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-5 py-4 border-b border-gray-100">
                            <h3 class="text-xs font-black text-gray-500 uppercase tracking-widest">Informacion General</h3>
                        </div>
                        <div class="p-5 space-y-5">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Cliente</label>
                                <p class="text-sm font-bold text-gray-800 leading-tight">{{ $quote->customer->name }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Contacto</label>
                                <p class="text-xs text-gray-600">{{ $quote->customer->phone ?? 'S/N' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Origen</label>
                                    <p class="text-xs font-bold text-gray-600 capitalize">{{ $quote->origin }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Vendedor</label>
                                    <p class="text-xs font-bold text-gray-600">{{ $quote->user->name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 rounded-2xl border border-amber-200 overflow-hidden">
                        <div class="bg-amber-100 px-5 py-3 border-b border-amber-200">
                            <h3 class="text-xs font-black text-amber-700 uppercase tracking-widest">Vigencia</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div>
                                <label class="block text-[10px] font-black text-amber-600 uppercase mb-1">Fecha de Emision</label>
                                <p class="text-sm font-bold text-amber-900">{{ $quote->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-amber-600 uppercase mb-1">Valida Hasta</label>
                                <p class="text-sm font-bold text-amber-900">{{ $quote->expires_at->format('d/m/Y') }}</p>
                            </div>
                            <div class="pt-2">
                                @php
                                    $now = now();
                                    $expiresAt = $quote->expires_at;
                                    
                                    $daysRemaining = (int) $now->diffInDays($expiresAt, false);
                                    $isExpired = $daysRemaining < 0;
                                @endphp
                                
                                <x-ui.badge :variant="$isExpired ? 'error' : 'success'" :dot="false" size="sm">
                                    @if($isExpired)
                                        Expirada hace {{ abs($daysRemaining) }} dia{{ abs($daysRemaining) !== 1 ? 's' : '' }}
                                    @else
                                        {{ $daysRemaining }} dia{{ $daysRemaining !== 1 ? 's' : '' }} disponible{{ $daysRemaining !== 1 ? 's' : '' }}
                                    @endif
                                </x-ui.badge>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-600 rounded-2xl p-5 shadow-md text-white">
                        <h4 class="text-[10px] font-black uppercase tracking-widest mb-4 opacity-80">Resumen Financiero</h4>
                        <div class="space-y-3 border-b border-indigo-400 pb-3 mb-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] opacity-70">Subtotal:</span>
                                <span class="text-sm font-bold">{{ config('regional.currency_symbol') }}{{ number_format($quote->subtotal, 2) }}</span>
                            </div>
                            @if($quote->discount_total > 0)
                            <div class="flex justify-between items-center text-amber-300">
                                <span class="text-[11px] opacity-90">Descuento:</span>
                                <span class="text-sm font-bold">-{{ config('regional.currency_symbol') }}{{ number_format($quote->discount_total, 2) }}</span>
                            </div>
                            @endif
                            {{-- Desglose real por tipo de impuesto (Fase 5, REQ-5.12) — mismo
                                 patrón que la factura (REQ-5.6). --}}
                            @foreach($quote->items->pluck('tax_breakdown')->filter()->flatten(1)->groupBy('key') as $key => $lines)
                                <div class="flex justify-between items-center">
                                    <span class="text-[11px] opacity-70">{{ $lines->first()['label'] }}:</span>
                                    <span class="text-sm font-bold">{{ config('regional.currency_symbol') }}{{ number_format($lines->sum('amount'), 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-black">TOTAL:</span>
                            <span class="text-2xl font-black">{{ config('regional.currency_symbol') }}{{ number_format($quote->grand_total, 2) }}</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                        <h4 class="text-[10px] font-black text-gray-600 uppercase tracking-widest mb-3">Auditoria del Sistema</h4>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Creada:</span>
                                <span class="font-bold text-gray-700">{{ $quote->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($quote->updated_at !== $quote->created_at)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Actualizada:</span>
                                <span class="font-bold text-gray-700">{{ $quote->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                </div>

                <div class="xl:col-span-3">
                    <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-3xl p-4 md:p-8 border border-gray-300 shadow-inner flex flex-col items-center justify-start min-h-[800px] overflow-auto">
                        
                        <div id="previewContainer" class="quote-frame-container is-letter">
                            <div class="bg-white shadow-2xl ring-1 ring-black/5 rounded-lg overflow-hidden h-full">
                                <iframe 
                                    id="quoteIframe"
                                    src="{{ route('sales.quotes.preview', $quote) }}" 
                                    class="w-full h-full border-0 bg-white"
                                    loading="lazy"
                                    title="Preview de Cotizacion">
                                </iframe>
                            </div>
                        </div>

                    </div>
                </div>

                <style>
                    .quote-frame-container {
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        background: white;
                    }

                    .is-letter {
                        width: 21.59cm;
                        height: 27.94cm;
                        transform: scale(0.7);
                        transform-origin: top center;
                    }

                    .is-ticket {
                        width: 85mm; 
                        min-height: 150mm;
                        height: 600px;
                    }

                    @media (max-width: 1280px) {
                        .is-letter { transform: scale(0.5); }
                        .is-ticket { transform: scale(0.9); }
                    }

                    @media (min-width: 1536px) {
                        .is-letter { transform: scale(0.85); }
                        .is-ticket { transform: scale(1.2); }
                    }

                    .floating-action-btn {
                        position: fixed;
                        bottom: 30px;
                        right: 30px;
                        z-index: 40;
                        width: 60px;
                        height: 60px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }

                    .floating-action-btn:hover {
                        transform: scale(1.1);
                        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
                    }

                    .floating-action-btn:active {
                        transform: scale(0.95);
                    }

                    @media (max-width: 768px) {
                        .floating-action-btn {
                            bottom: 20px;
                            right: 20px;
                            width: 56px;
                            height: 56px;
                        }
                    }
                </style>
            </div>
        </div>
    </div>

    {{-- BOTON FLOTANTE PARA CONVERTIR A VENTA --}}
    @if($quote->status === 'approved' && !$quote->sale_id)
    <button x-data @click="$dispatch('open-modal', 'confirm-convert-quote-{{ $quote->id }}')" 
            class="floating-action-btn bg-emerald-600 text-white hover:bg-emerald-700">
        <x-heroicon-s-arrow-down-circle class="w-6 h-6" />
    </button>
    @endif

    {{-- INCLUIR EL MODAL DE CONVERTIR A VENTA --}}
    @php
        $config = general_config();
    @endphp
    
    @include('sales.quotes.partials.modal-convert', [
        'quote' => $quote,
        'config' => $config,
        'tipo_pagos' => $tipo_pagos ?? [],
        'ncf_types' => $ncf_types ?? [],
        'warehouses' => $warehouses ?? []
    ])

    <script>
        const config = {
            letter: "{{ route('sales.quotes.print', ['quote' => $quote, 'format' => 'letter']) }}",
            ticket: "{{ route('sales.quotes.print', ['quote' => $quote, 'format' => 'ticket']) }}"
        };

        const formatSelector = document.getElementById('formatSelector');
        const printBtn = document.getElementById('printBtn');
        const downloadBtn = document.getElementById('downloadBtn');
        const previewContainer = document.getElementById('previewContainer');
        const quoteIframe = document.getElementById('quoteIframe');

        formatSelector.addEventListener('change', function() {
            const format = this.value;
            const url = config[format];
            
            printBtn.href = url;
            downloadBtn.href = url + '?download=1';
            downloadBtn.style.display = format === 'letter' ? 'inline-flex' : 'none';
            
            previewContainer.classList.remove('is-letter', 'is-ticket');
            previewContainer.classList.add(`is-${format}`);
            
            quoteIframe.src = "{{ route('sales.quotes.preview', $quote) }}?format=" + format;
        });
    </script>

</x-app-layout>