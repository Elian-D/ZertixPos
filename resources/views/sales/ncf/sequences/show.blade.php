{{-- Detalle de la secuencia NCF — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Fila 1: secuencia | consumo (50/50). Fila 2: últimos NCF emitidos.
     Recibe $sequence (type cargado), $recentLogs, $issuedCount, $voidedCount,
     $logLimit y $ncf_types (vacío; lo pide el partial de modales). --}}
@use('App\Models\Sales\Ncf\NcfSequence')
@use('App\Models\Sales\Ncf\NcfLog')
@php
    $total = max(0, $sequence->to - $sequence->from + 1);
    $used = max(0, $sequence->current - $sequence->from + 1);
    $available = max(0, $sequence->to - $sequence->current);
    $usedPct = $total > 0 ? min(100, round($used / $total * 100)) : 0;
    $next = max($sequence->current + 1, $sequence->from);
    $status = $sequence->calculated_status;
    $isExpired = $status === NcfSequence::STATUS_EXPIRED;
    $isExhausted = $status === NcfSequence::STATUS_EXHAUSTED;
    $isLow = ! $isExpired && ! $isExhausted && $sequence->isLow();
    $daysToExpiry = (int) now()->startOfDay()->diffInDays($sequence->expiry_date->copy()->startOfDay(), false);
    $isVirgin = $sequence->current < $sequence->from;
@endphp

<x-app-layout :title="'Secuencia '.$sequence->series.$sequence->type->code">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Secuencia '.$sequence->series.$sequence->type->code" :description="'Ver secuencia NCF · '.$sequence->type->name">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.ncf.sequences.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($isVirgin)
                    <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-trash"
                        x-data @click="$dispatch('open-modal', 'confirm-sequence-deletion-{{ $sequence->id }}')">
                        Eliminar lote
                    </x-ui.button>
                @endif
                <x-ui.button variant="secondary" appearance="outline" iconLeft="heroicon-s-bell-alert"
                    x-data @click="$dispatch('open-modal', 'threshold-sequence-{{ $sequence->id }}')">
                    Umbral de alerta
                </x-ui.button>
                <x-ui.button variant="primary" iconLeft="heroicon-s-arrow-trending-up"
                    x-data @click="$dispatch('open-modal', 'extend-sequence-{{ $sequence->id }}')">
                    Ampliar rango
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if($isExpired)
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-x-circle class="w-5 h-5 shrink-0" />
                Secuencia vencida el {{ $sequence->expiry_date->format('d/m/Y') }} — ya no se puede usar para emitir comprobantes.
            </div>
        @elseif($isExhausted)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 flex items-center gap-2 text-sm text-amber-800">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 shrink-0" />
                Secuencia agotada — amplía el rango o registra un lote nuevo para seguir emitiendo.
            </div>
        @elseif($isLow)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 flex items-center gap-2 text-sm text-amber-800">
                <x-heroicon-s-bell-alert class="w-5 h-5 shrink-0" />
                Quedan <strong>{{ number_format($available) }}</strong> NCF — por debajo del umbral de alerta ({{ number_format($sequence->alert_threshold) }}).
            </div>
        @endif

        {{-- Fila 1: secuencia | consumo --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <x-ui.infolist.section title="Secuencia" icon="heroicon-o-shield-check" :cols="2">
                <x-ui.infolist.entry label="Tipo de comprobante" :value="$sequence->type->code.' — '.$sequence->type->name" strong />
                <x-ui.infolist.entry label="Estado">
                    <x-ui.badge :variant="$sequence->status_variant" size="sm" :dot="false">
                        {{ $isLow ? 'Por agotarse' : $sequence->status_label }}
                    </x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Serie" :value="$sequence->series" />
                <x-ui.infolist.entry label="Modalidad">
                    <x-ui.badge :variant="$sequence->type->is_electronic ? 'primary' : 'slate'" size="sm" :dot="false">
                        {{ $sequence->type->is_electronic ? 'Electrónico (e-CF)' : 'Impreso' }}
                    </x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Desde">
                    <span class="font-mono">{{ $sequence->formatNumber($sequence->from) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Hasta">
                    <span class="font-mono">{{ $sequence->formatNumber($sequence->to) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Vence">
                    <span @class(['font-medium text-state-error' => $isExpired, 'text-amber-600' => ! $isExpired && $daysToExpiry <= 30])>
                        {{ $sequence->expiry_date->format('d/m/Y') }}
                    </span>
                    <span class="block text-xs text-gray-400">
                        @if($daysToExpiry > 0)
                            en {{ $daysToExpiry }} {{ $daysToExpiry === 1 ? 'día' : 'días' }}
                        @elseif($daysToExpiry === 0)
                            vence hoy
                        @else
                            hace {{ abs($daysToExpiry) }} {{ abs($daysToExpiry) === 1 ? 'día' : 'días' }}
                        @endif
                    </span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Umbral de alerta" :value="number_format($sequence->alert_threshold).' NCF'" />
                <x-ui.infolist.entry label="Registrada" :value="$sequence->created_at->format('d/m/Y h:i A')" />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Consumo" icon="heroicon-o-chart-bar" :cols="2">
                <x-ui.infolist.entry label="Próximo NCF a emitir" full>
                    @if($isExpired || $isExhausted)
                        <span class="text-gray-400">No se emiten más NCF de esta secuencia</span>
                    @else
                        <span class="font-mono text-xl font-bold text-zertix-primary-700">{{ $sequence->formatNumber($next) }}</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Disponibles">
                    <span @class([
                        'text-2xl font-bold',
                        'text-state-error' => $available <= 0,
                        'text-amber-600' => $available > 0 && $isLow,
                        'text-emerald-600' => $available > 0 && ! $isLow,
                    ])>{{ number_format($available) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Usados">
                    <span class="text-2xl font-bold text-gray-900">{{ number_format($used) }}</span>
                    <span class="text-gray-500">de {{ number_format($total) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Uso del rango" full>
                    <span class="block h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <span @class([
                            'block h-full rounded-full',
                            'bg-state-error' => $usedPct >= 100,
                            'bg-amber-500' => $usedPct < 100 && $isLow,
                            'bg-zertix-primary-500' => $usedPct < 100 && ! $isLow,
                        ]) style="width: {{ $usedPct }}%"></span>
                    </span>
                    <span class="mt-1 block text-xs text-gray-500">{{ $usedPct }}% usado</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Anulados" :value="number_format($voidedCount)" />
                <x-ui.infolist.entry label="Registrados en el log" :value="number_format($issuedCount)" />
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: últimos NCF emitidos --}}
        <x-ui.infolist.section title="Últimos NCF emitidos" icon="heroicon-o-clock" :cols="0"
            :description="$issuedCount > $logLimit ? 'Los '.$logLimit.' más recientes de '.number_format($issuedCount) : number_format($issuedCount).' emitidos'">
            <x-ui.infolist.repeatable :empty="$recentLogs->isEmpty()" emptyIcon="heroicon-o-shield-check"
                emptyTitle="Sin NCF emitidos" emptyDescription="Esta secuencia todavía no ha asignado ningún comprobante.">
                @foreach($recentLogs as $log)
                    @php $isVoided = $log->status === NcfLog::STATUS_VOIDED; @endphp
                    <x-ui.infolist.repeatable-item :cols="5" :class="$isVoided ? 'opacity-60' : ''">
                        <x-ui.infolist.entry label="NCF">
                            <span @class(['font-mono font-semibold text-gray-900', 'line-through' => $isVoided])>{{ $log->full_ncf }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Fecha" :value="$log->created_at->format('d/m/Y h:i A')" />
                        <x-ui.infolist.entry label="Venta" :value="$log->sale?->number"
                            :href="$log->sale && auth()->user()->can('sales.view') ? route('sales.show', $log->sale) : null" />
                        <x-ui.infolist.entry label="Cliente" :value="$log->sale?->client?->display_name" />
                        <x-ui.infolist.entry label="Estado" class="sm:text-right">
                            <x-ui.badge :variant="$isVoided ? 'error' : 'success'" size="sm" :dot="false">
                                {{ NcfLog::getStatuses()[$log->status] ?? $log->status }}
                            </x-ui.badge>
                            @if($isVoided && $log->cancellation_reason)
                                <span class="block mt-1 text-xs text-gray-400">{{ $log->cancellation_reason }}</span>
                            @endif
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>

            @if($issuedCount > $logLimit)
                <p class="mt-4 text-xs text-slate-500">
                    <a href="{{ route('finance.ncf.logs.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todo el log NCF</a>
                </p>
            @endif
        </x-ui.infolist.section>
    </div>

    {{-- Modales de ampliar, umbral y eliminar (mismo partial del listado) --}}
    @include('sales.ncf.sequences.partials.modals', ['items' => collect([$sequence])])
</x-app-layout>
