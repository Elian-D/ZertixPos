{{-- MODAL VER DETALLE DEL NCF — mismo lenguaje que las vistas show (Infolist,
     docs/ui/infolist.md): encabezado blanco, pares etiqueta/valor, sin degradados. --}}
@foreach($items as $log)
    @php
        $isVoided = $log->status === \App\Models\Sales\Ncf\NcfLog::STATUS_VOIDED;
        $sale = $log->sale;
    @endphp
    <x-modal name="view-log-{{ $log->id }}" maxWidth="2xl">
        {{-- Encabezado --}}
        <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-100">
            <div class="min-w-0">
                <p class="text-xs text-slate-500">{{ $log->type->code }} — {{ $log->type->name }}</p>
                <h3 @class(['mt-0.5 font-mono text-xl font-bold text-gray-900', 'line-through text-gray-400' => $isVoided])>{{ $log->full_ncf }}</h3>
            </div>
            <x-ui.badge :variant="$isVoided ? 'error' : 'success'" size="sm" :dot="false">
                {{ \App\Models\Sales\Ncf\NcfLog::getStatuses()[$log->status] ?? $log->status }}
            </x-ui.badge>
        </div>

        {{-- Datos --}}
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-7 px-6 py-6">
            <x-ui.infolist.entry label="Venta" :value="$sale?->number" strong
                :href="$sale && auth()->user()->can('sales.view') ? route('sales.show', $sale) : null" />
            <x-ui.infolist.entry label="Total de la venta" :value="$sale ? config('regional.currency_symbol').number_format($sale->grand_total, 2) : null" />
            <x-ui.infolist.entry label="Cliente" :value="$sale?->client?->name ?? 'Consumidor Final'" />
            <x-ui.infolist.entry label="RNC / Cédula" :value="$sale?->client?->tax_id" />
            <x-ui.infolist.entry label="Emitido" :value="$log->created_at->format('d/m/Y h:i A')" />
            <x-ui.infolist.entry label="Registrado por" :value="$log->user?->name ?? 'Sistema'" />
            @if($isVoided)
                <x-ui.infolist.entry label="Motivo de anulación" full>
                    <span class="text-state-error">{{ $log->cancellation_reason ?? 'No se especificó un motivo.' }}</span>
                </x-ui.infolist.entry>
            @endif
        </dl>

        {{-- Acciones --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cerrar</x-ui.button>
            {{-- $sale->invoice (no sale_id): la factura tiene su propio id, distinto al de la venta. --}}
            @if($sale?->invoice && auth()->user()->can('invoices.view'))
                <x-ui.button href="{{ route('finance.invoices.show', $sale->invoice) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-document-text">
                    Ver factura
                </x-ui.button>
            @endif
            @if($sale && auth()->user()->can('sales.view'))
                <x-ui.button href="{{ route('sales.show', $sale) }}" variant="primary" iconLeft="heroicon-s-eye">
                    Ver venta
                </x-ui.button>
            @endif
        </div>
    </x-modal>
@endforeach
