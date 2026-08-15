@foreach($items as $payment)
    {{-- 1. MODAL: VISTA DE DETALLE DEL COBRO --}}
    <x-modal name="view-payment-{{ $payment->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl bg-white shadow-2xl">
            {{-- Header --}}
            <div class="bg-gray-50 px-8 py-6 border-b flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-black text-gray-900 tracking-tight">Comprobante de Cobro</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                            Recibo No. {{ $payment->receipt_number }}
                        </span>
                        <span class="text-gray-300 text-xs">•</span>
                        <span class="text-xs text-gray-500 italic">Ref: {{ $payment->reference ?? 'Sin Referencia' }}</span>
                    </div>
                </div>

                {{-- Estado Dinámico --}}
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset shadow-sm {{ $payment->status_style }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-2 bg-current {{ $payment->status === 'active' ? 'animate-pulse' : '' }}"></span>
                    {{ strtoupper($payment->status_label) }}
                </span>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Columna Izquierda: Datos del Cliente y Método --}}
                    <div class="space-y-6">
                        <div class="flex gap-3">
                            <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 shrink-0">
                                <x-heroicon-s-user class="w-5 h-5"/>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Cliente</span>
                                <p class="text-sm font-bold text-gray-800">{{ $payment->client->name }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 shrink-0">
                                <x-heroicon-s-credit-card class="w-5 h-5"/>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Método de Pago</span>
                                <p class="text-sm font-semibold text-gray-700">{{ $payment->tipoPago->nombre ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 shrink-0">
                                <x-heroicon-s-calendar class="w-5 h-5"/>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Fecha de Aplicación</span>
                                <p class="text-sm font-semibold text-gray-700">{{ $payment->payment_date->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Columna Derecha: Resumen Financiero --}}
                    <div class="space-y-6">
                        <div class="p-5 {{ $payment->status === 'active' ? 'bg-emerald-900' : 'bg-red-900' }} rounded-2xl shadow-lg relative overflow-hidden text-white">
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                            
                            <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest block mb-4">Monto Transado</span>
                            
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs opacity-80">Documento Aplicado:</span>
                                <span class="text-sm font-mono">{{ $payment->receivable->document_number ?? 'Anticipo' }}</span>
                            </div>

                            <hr class="border-white/10 my-3">
                            
                            <div class="flex justify-between items-end">
                                <div>
                                    <span class="text-[10px] font-bold uppercase block opacity-60 italic">Total Recibido</span>
                                    <span class="text-2xl font-black tracking-tight">{{ config('regional.currency_symbol') }}{{ number_format($payment->amount, 2) }}</span>
                                </div>
                                <x-heroicon-s-banknotes class="w-10 h-10 text-white/20"/>
                            </div>
                        </div>

                        {{-- Info de Contabilización — solo con accounting.advanced activo
                             (REQ-02.14): con el módulo apagado journal_entry_id siempre es
                             null (ver CollectionService::createCollection/REQ-02.8), así que este
                             bloque mostraba permanentemente "Pendiente de Contabilizar",
                             dando a entender que algo quedó a medias cuando el módulo nunca
                             debió mostrarse acá. --}}
                        @if (module_enabled('accounting.advanced'))
                            <div class="flex gap-3 px-1">
                                <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center text-gray-400 shrink-0">
                                    <x-heroicon-s-book-open class="w-5 h-5"/>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Asiento Contable</span>
                                    @if($payment->journal_entry_id)
                                        <p class="text-xs font-mono text-indigo-600">
                                            #{{ str_pad($payment->journal_entry_id, 6, '0', STR_PAD_LEFT) }} <br>
                                            <span class="font-sans font-bold text-gray-800">Transacción Contabilizada</span>
                                        </p>
                                    @else
                                        <p class="text-xs text-amber-500 italic">Pendiente de Contabilizar</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Nota --}}
                    <div class="col-span-1 md:col-span-2">
                        <div class="bg-gray-50 p-4 rounded-xl border border-dashed border-gray-200">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Nota del Recibo</span>
                            <p class="text-sm text-gray-700 leading-relaxed italic">
                                "{{ $payment->note ?? 'Sin observaciones adicionales.' }}"
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between text-[11px] text-gray-400 uppercase font-bold tracking-tighter">
                    <span>Registrado por: {{ $payment->creator->name ?? 'Sistema' }}</span>
                    <span>Sistema: {{ $payment->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
                <a href="{{ route('finance.collections.print', $payment->id) }}"
                target="_blank"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition shadow-sm">
                    <x-heroicon-s-printer class="w-3 h-3 mr-2"/> Imprimir Recibo PDF
                </a>
            </div>
        </div>
    </x-modal>

    {{-- 2. MODAL: ANULACIÓN DE COBRO --}}
    <x-modal name="confirm-cancel-payment-{{ $payment->id }}" maxWidth="sm">
        <form action="{{ route('finance.collections.cancel', $payment) }}" method="POST" class="p-6 text-center">
            @csrf

            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-s-no-symbol class="w-10 h-10"/>
            </div>

            <h3 class="text-lg font-bold text-gray-900">¿Anular este Cobro?</h3>
            <p class="text-sm text-gray-500 mt-2">
                Se anulará el recibo <strong>{{ $payment->receipt_number }}</strong>.
                <span class="block mt-2 font-bold text-red-600 bg-red-50 p-2 rounded border border-red-100">
                    Esto revertirá el saldo de la cuenta por cobrar{{ module_enabled('accounting.advanced') ? ' y anulará su asiento contable' : '' }}.
                </span>
            </p>

            <div class="mt-8 flex justify-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">No, mantener</x-secondary-button>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white text-xs font-bold uppercase rounded-lg hover:bg-red-700 shadow-lg shadow-red-200">
                    Sí, Anular Cobro
                </button>
            </div>
        </form>
    </x-modal>
@endforeach