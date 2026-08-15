{{-- MODAL VER DETALLE / LOGS RÁPIDOS --}}
@foreach($items as $log)
<x-modal name="view-log-{{ $log->id }}" maxWidth="2xl">
    <div class="overflow-hidden rounded-xl bg-white shadow-2xl">
        {{-- Header dinámico --}}
        @php
            $logStyle = \App\Models\Sales\Ncf\NcfLog::getStatusStyles()[$log->status] ?? 'bg-gray-100 text-gray-700';
            $logLabel = \App\Models\Sales\Ncf\NcfLog::getStatuses()[$log->status] ?? $log->status;
        @endphp

        <div class="bg-gray-900 px-8 py-6 flex justify-between items-center text-white">
            <div>
                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-[0.2em] block mb-1">
                    {{ $log->type->name }}
                </span>
                <h3 class="text-2xl font-mono font-black tracking-widest">{{ $log->full_ncf }}</h3>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset shadow-md {{ $logStyle }}">
                    {{ strtoupper($logLabel) }}
                </span>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                {{-- Sección de la Transacción --}}
                <div class="space-y-4">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider border-b pb-2">Datos de la Venta</h4>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-gray-500">
                            <x-heroicon-s-document-text class="w-4 h-4"/>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Factura y Monto</p>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-indigo-600">#{{ $log->sale->number ?? 'N/A' }}</span>
                                <span class="text-xs font-medium text-gray-500">Total: {{ config('regional.currency_symbol') }} {{ number_format($log->sale->total_amount ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-gray-500">
                            <x-heroicon-s-calendar class="w-4 h-4"/>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Fecha de Emisión</p>
                            <p class="text-sm font-medium text-gray-700">{{ $log->created_at->format('d/m/Y h:i A') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Sección del Cliente --}}
                <div class="space-y-4">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider border-b pb-2">Información Fiscal Cliente</h4>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-indigo-50 rounded flex items-center justify-center text-indigo-600">
                            <x-heroicon-s-user class="w-4 h-4"/>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Razón Social</p>
                            <p class="text-sm font-bold text-gray-800">{{ $log->sale->client->name ?? 'Consumidor Final' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-indigo-50 rounded flex items-center justify-center text-indigo-600">
                            <x-heroicon-s-identification class="w-4 h-4"/>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">RNC / Cédula</p>
                            <p class="text-sm font-mono text-gray-700">{{ $log->sale->client->tax_id ?? '00000000000' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detalle del Tipo de Comprobante --}}
            <div class="mb-8 p-4 bg-gray-50 rounded-xl border border-gray-100">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500 font-bold uppercase">Configuración de Origen</span>
                    <span class="px-2 py-0.5 bg-white border rounded text-gray-400 font-mono">Tipo: {{ $log->type->code }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-600">Este comprobante pertenece a la secuencia de <strong>{{ $log->type->name }}</strong>.</p>
            </div>

            {{-- Alerta de Anulación --}}
            @if($log->status === 'canceled' || $log->status === 'voided')
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-8">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-red-600"/>
                        <span class="text-[10px] font-black text-red-600 uppercase">Motivo de Anulación</span>
                    </div>
                    <p class="text-sm text-red-800 italic">"{{ $log->cancellation_reason ?? 'No se especificó un motivo.' }}"</p>
                </div>
            @endif

            {{-- Auditoría de Usuario --}}
            <div class="bg-gray-50 rounded-lg p-4 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-indigo-600 rounded-full flex items-center justify-center text-[10px] font-bold text-white uppercase">
                        {{ substr($log->user->name ?? 'S', 0, 1) }}
                    </div>
                    <span class="text-[11px] text-gray-500 font-medium">Registrado por: <strong>{{ $log->user->name ?? 'Sistema' }}</strong></span>
                </div>
                <span class="text-[10px] text-gray-400 font-mono">ID Registro: #{{ str_pad($log->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>

        <div class="px-8 py-4 bg-gray-50 border-t flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
            @if($log->sale_id)
                <a href="{{ route('finance.invoices.print', $log->sale_id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-[10px] font-black uppercase hover:bg-indigo-700 transition shadow-sm"  target="_blank>
                    <x-heroicon-s-eye class="w-3 h-3 mr-2"/> Ver Factura
                </a>
            @endif
        </div>
    </div>
</x-modal>
@endforeach