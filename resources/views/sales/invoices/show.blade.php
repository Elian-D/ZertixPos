<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header Principal --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex-1">
                    <a href="{{ route('finance.invoices.index') }}" class="inline-flex items-center text-zertix-primary-600 hover:text-zertix-primary-800 text-sm font-semibold transition mb-3">
                        <x-heroicon-s-arrow-left class="w-4 h-4 mr-1.5" />
                        Regresar al Historial
                    </a>
                    <div class="flex items-center gap-3">
                        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                            Factura <span class="text-zertix-primary-600">{{ $invoice->invoice_number }}</span>
                        </h2>
                        <x-ui.badge :variant="$invoice->status === 'active' ? 'success' : 'error'" :dot="false">
                            {{ $invoice->status === 'active' ? 'Vigente' : 'Anulada' }}
                        </x-ui.badge>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-ui.forms.select id="formatSelector" placeholder="">
                        <option value="ticket">Ticket (Térmica)</option>
                        <option value="letter">Carta (PDF)</option>
                    </x-ui.forms.select>

                    <a id="printBtn" 
                       href="{{ route('finance.invoices.print', $invoice) }}?format=ticket" 
                       target="_blank" 
                       class="inline-flex items-center px-5 py-2.5 bg-gray-900 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-gray-800 shadow-lg active:scale-95 transition-all">
                        <x-heroicon-s-printer class="w-4 h-4 mr-2" />
                        Imprimir
                    </a>
                    
                    <a id="downloadBtn"
                       href="{{ route('finance.invoices.print', $invoice) }}?format=letter&download=1" 
                       class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg font-bold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 active:scale-95 transition-all"
                       target="blank">
                        <x-heroicon-s-arrow-down-tray class="w-4 h-4 mr-2" />
                        Descargar PDF
                    </a>
                </div>
            </div>

            {{-- Grid de Contenido --}}
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                
                {{-- Lateral: Información y Auditoría (1 Columna) --}}
                <div class="xl:col-span-1 space-y-6">

                    {{-- NUEVO: BLOQUE FISCAL RESALTADO --}}
                    @if($invoice->sale->ncf)
                    <div class="bg-white rounded-2xl shadow-sm border-2 border-zertix-primary-100 overflow-hidden">
                        <div class="bg-zertix-primary-50 px-5 py-3 border-b border-zertix-primary-100 flex justify-between items-center">
                            <h3 class="text-[10px] font-black text-zertix-primary-600 uppercase tracking-widest">Datos Fiscales (DGII)</h3>
                            <x-heroicon-s-check-badge class="w-4 h-4 text-zertix-primary-500"/>
                        </div>
                        <div class="p-5">
                            <div class="mb-4">
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Número de Comprobante</label>
                                <p class="text-xl font-mono font-black text-gray-900 tracking-tighter">{{ $invoice->sale->ncf }}</p>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-[11px] text-gray-500 italic">Tipo:</span>
                                    <span class="text-[11px] font-bold text-gray-700">{{ $invoice->sale->ncfLog->type->name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[11px] text-gray-500 italic">Vencimiento:</span>
                                    <span class="text-[11px] font-bold text-gray-700">
                                        {{ $invoice->sale->ncfLog?->sequence?->expiry_date?->format('d/m/Y') ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 px-5 py-4 border-b border-gray-100">
                            <h3 class="text-xs font-black text-gray-500 uppercase tracking-widest">Información General</h3>
                        </div>
                        <div class="p-5 space-y-5">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Cliente</label>
                                <p class="text-sm font-bold text-gray-800 leading-tight">{{ $invoice->sale->client->name }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Venta de Origen</label>
                                <p class="text-sm font-bold text-zertix-primary-600">#{{ $invoice->sale->number }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Formato</label>
                                    <p class="text-xs font-bold text-gray-600 capitalize">{{ $invoice->format_type }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Condición</label>
                                    <p class="text-xs font-bold text-gray-600 capitalize">{{ $invoice->type }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-zertix-primary-600 rounded-2xl p-5 shadow-md text-white">
                        <h4 class="text-[10px] font-black uppercase tracking-widest mb-3 opacity-80">Auditoría del Sistema</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] opacity-70 italic">Generado:</span>
                                <span class="text-xs font-bold">{{ $invoice->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] opacity-70 italic">Usuario:</span>
                                <span class="text-xs font-bold">{{ $invoice->generated_by }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Alerta de Anulación (Si aplica) --}}
                    @if($invoice->status !== 'active')
                    <div class="bg-red-50 rounded-2xl p-5 border border-red-100">
                        <div class="flex items-center gap-2 mb-2 text-red-700">
                            <x-heroicon-s-x-circle class="w-5 h-5"/>
                            <span class="text-xs font-black uppercase">Documento Anulado</span>
                        </div>
                        <p class="text-[11px] text-red-600 italic">
                            Motivo: {{ $invoice->sale->ncfLog->cancellation_reason ?? 'Sin motivo registrado' }}
                        </p>
                    </div>
                    @endif
                </div>

                {{-- Central: Preview con iframe --}}
                <div class="xl:col-span-3">
                    <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-3xl p-4 md:p-8 border border-gray-300 shadow-inner flex flex-col items-center justify-start min-h-[800px] overflow-auto">
                        
                        {{-- Contenedor dinámico --}}
                        <div class="invoice-frame-container {{ $invoice->format_type === 'letter' ? 'is-letter' : 'is-ticket' }}">
                            <div class="bg-white shadow-2xl ring-1 ring-black/5 rounded-lg overflow-hidden h-full">
                                {{-- 
                                Añadimos loading="lazy" para la percepción de carga 
                                y forzamos un fondo blanco mientras carga 
                                --}}
                                <iframe 
                                    src="{{ route('finance.invoices.preview', $invoice) }}" 
                                    class="w-full h-full border-0 bg-white"
                                    loading="lazy"
                                    id="invoice-iframe"
                                    title="Preview de Factura">
                                </iframe>
                            </div>
                        </div>

                    </div>
                </div>

                <style>
                    /* Contenedor Base */
                    .invoice-frame-container {
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        background: white;
                    }

                    /* Estilos para CARTA (Letter) */
                    .is-letter {
                        width: 21.59cm; /* Ancho real carta */
                        height: 27.94cm; /* Alto real carta */
                        transform: scale(0.7); /* Reducimos para que quepa en pantalla de laptop */
                        transform-origin: top center;
                    }

                    /* Estilos para TICKET (80mm o 58mm) */
                    .is-ticket {
                        width: 85mm; 
                        min-height: 150mm;
                        height: 600px; /* Altura fija para scroll interno del iframe */
                    }

                    /* Ajustes Responsivos para que no se salga de la pantalla */
                    @media (max-width: 1280px) {
                        .is-letter { transform: scale(0.5); }
                        .is-ticket { transform: scale(0.9); }
                    }

                    @media (min-width: 1536px) {
                        .is-letter { transform: scale(0.85); }
                        .is-ticket { transform: scale(1.2); }
                    }
                </style>
            </div>
        </div>
    </div>

    <style>
        /* Contenedor Base */
        .invoice-frame-container {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        /* Estilos para CARTA (Letter) */
        .is-letter {
            width: 21.59cm;
            height: 27.94cm;
            transform: scale(0.7);
            transform-origin: top center;
        }

        /* Estilos para TICKET (80mm o 58mm) */
        .is-ticket {
            width: 85mm; 
            min-height: 150mm;
            height: 600px;
        }

        /* Ajustes Responsivos */
        @media (max-width: 1280px) {
            .is-letter { transform: scale(0.5); }
            .is-ticket { transform: scale(0.9); }
        }

        @media (min-width: 1536px) {
            .is-letter { transform: scale(0.85); }
            .is-ticket { transform: scale(1.2); }
        }
    </style>

    <script>
        const config = {
            letter: "{{ route('finance.invoices.print', $invoice) }}?format=letter",
            ticket: "{{ route('finance.invoices.print', $invoice) }}?format=ticket"
        };

        const formatSelector = document.getElementById('formatSelector');
        const printBtn = document.getElementById('printBtn');
        const downloadBtn = document.getElementById('downloadBtn');
        const previewContainer = document.querySelector('.invoice-frame-container');
        const invoiceIframe = document.getElementById('invoice-iframe');

        formatSelector.addEventListener('change', function() {
            const format = this.value;
            const url = config[format];
            
            printBtn.href = url;
            downloadBtn.href = url + '&download=1';
            downloadBtn.style.display = format === 'letter' ? 'inline-flex' : 'none';
            
            previewContainer.classList.remove('is-letter', 'is-ticket');
            previewContainer.classList.add(`is-${format}`);
            
            invoiceIframe.src = "{{ route('finance.invoices.preview', $invoice) }}?format=" + format;
        });
    </script>
</x-app-layout>