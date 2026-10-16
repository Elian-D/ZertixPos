{{-- Reporte de NCF (v1.5.0 Fase 6): disponibles, duración al ritmo actual, alertas de
     secuencias, emisión por día y por tipo, anulaciones y últimas emisiones. Recibe $period,
     $types, $typeId, $stats, $byDay, $byType, $voidReasons, $sequences, $alerts y $recent
     (App\Http\Controllers\Reports\NcfReportController). --}}
@php
    $fmt = fn ($n) => number_format((float) $n);
    $byTypeTotal = max($byType->sum('total'), 1);
@endphp

<x-app-layout title="Reporte de NCF">
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Reporte de NCF" :description="$period->label().' · comprobantes fiscales'">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.ncf.sequences.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-queue-list">
                    Secuencias
                </x-ui.button>
                <x-ui.button href="{{ route('finance.ncf.logs.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-document-text">
                    Historial
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.report.filters :cols="3">
            <x-ui.forms.input type="date" label="Desde" name="from" :value="$period->from->toDateString()" />
            <x-ui.forms.input type="date" label="Hasta" name="to" :value="$period->to->toDateString()" />
            <x-ui.forms.select label="Tipo de comprobante" name="ncf_type" placeholder="Todos los tipos">
                @foreach($types as $type)
                    <option value="{{ $type->id }}" @selected($typeId === $type->id)>{{ $type->code }} · {{ $type->name }}</option>
                @endforeach
            </x-ui.forms.select>
        </x-ui.report.filters>

        @foreach($alerts as $alert)
            <x-ui.alert :variant="$alert['variant']">{{ $alert['text'] }}</x-ui.alert>
        @endforeach

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-ui.stat label="Disponibles" :value="$fmt($stats['available'])"
                :hint="$stats['days_remaining'] !== null ? 'Alcanzan para ~'.$fmt($stats['days_remaining']).' días' : 'Sin consumo en el período'"
                :variant="$stats['available'] <= 0 ? 'error' : 'default'" />
            <x-ui.stat label="Emitidos" :value="$fmt($stats['emitted'])" :hint="$stats['daily_average'].' por día en promedio'" />
            <x-ui.stat label="Anulados" :value="$fmt($stats['voided'])" :hint="$stats['void_rate'].'% de lo emitido'"
                :variant="$stats['void_rate'] > 10 ? 'error' : 'default'" />
            <x-ui.stat label="Próximo vencimiento" :value="$stats['next_expiry']?->format('d/m/Y') ?? '—'"
                :hint="$stats['next_expiry'] ? 'En '.(int) now()->startOfDay()->diffInDays($stats['next_expiry'], false).' días' : 'Sin secuencias vigentes'" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <x-ui.infolist.section title="Emisión por día" icon="heroicon-o-chart-bar" :cols="0" class="lg:col-span-2">
                <x-ui.report.bar-chart :bars="$byDay" empty="Sin NCF emitidos en el período." />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Por tipo" icon="heroicon-o-tag" :cols="0">
                <x-ui.report.rows empty="Sin NCF emitidos en el período.">
                    @foreach($byType as $row)
                        <li class="py-2.5">
                            <div class="flex items-center gap-3">
                                <span class="flex-1 min-w-0 text-sm text-gray-800 truncate"><span class="font-mono font-semibold">{{ $row->code }}</span> · {{ $row->name }}</span>
                                <span class="text-sm font-semibold tabular-nums text-gray-900">{{ $fmt($row->total) }}</span>
                            </div>
                            <x-ui.report.progress class="mt-1.5" :value="$row->total / $byTypeTotal * 100" />
                        </li>
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Secuencias activas" icon="heroicon-o-queue-list" :cols="0" description="Usado de cada secuencia">
                <x-ui.report.rows empty="No hay secuencias activas.">
                    @foreach($sequences as $seq)
                        <li class="py-2.5">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('finance.ncf.sequences.show', $seq->id) }}" class="flex-1 min-w-0 text-sm text-gray-800 hover:underline truncate">{{ $seq->label }}</a>
                                @if($seq->expired)
                                    <x-ui.badge variant="error" size="sm" :dot="false">Vencida</x-ui.badge>
                                @elseif($seq->low)
                                    <x-ui.badge variant="warning" size="sm" :dot="false">Por agotarse</x-ui.badge>
                                @endif
                                <span class="text-xs text-slate-400 tabular-nums">vence {{ $seq->expiry_date->format('d/m/Y') }}</span>
                            </div>
                            <div class="mt-1.5 flex items-center gap-3">
                                <x-ui.report.progress :value="$seq->progress" :variant="$seq->expired || $seq->remaining <= 0 ? 'error' : ($seq->low ? 'warning' : 'primary')" />
                                <span class="shrink-0 text-xs text-slate-500 tabular-nums">{{ $fmt($seq->remaining) }} quedan</span>
                            </div>
                        </li>
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>

            <div class="flex flex-col gap-6">
                <x-ui.infolist.section title="Últimas emisiones" icon="heroicon-o-document-text" :cols="0">
                    <x-ui.report.rows empty="Sin NCF en el período.">
                        @foreach($recent as $log)
                            <x-ui.report.row :label="$log->full_ncf" :sub="($log->type->code ?? '').' · '.($log->sale->client->name ?? 'Sin cliente').' · '.$log->created_at->format('d/m/Y h:i A')">
                                <x-slot:badge>
                                    @if($log->status === \App\Models\Sales\Ncf\NcfLog::STATUS_VOIDED)
                                        <x-ui.badge variant="slate" size="sm" :dot="false">Anulado</x-ui.badge>
                                    @endif
                                </x-slot:badge>
                            </x-ui.report.row>
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Motivos de anulación" icon="heroicon-o-x-circle" :cols="0">
                    <x-ui.report.rows empty="Sin anulaciones en el período.">
                        @foreach($voidReasons as $row)
                            <x-ui.report.row :label="$row->reason" :value="$fmt($row->total)" />
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>
            </div>
        </div>
    </div>
</x-app-layout>
