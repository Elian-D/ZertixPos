{{-- Estado de resultados (v1.5.0 REQ-6.1): ventas netas − costo = ganancia bruta; − pérdidas
     de inventario − gastos = resultado del período. Recibe $period, $statement
     (BusinessMetrics::incomeStatement) y $reasons (motivos de merma). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '−' : '').$currency.number_format(abs((float) $v), 2);
    $s = $statement;
    $l = $s['losses'];
    $grossMargin = $s['net_sales'] > 0 ? number_format($s['gross_profit'] / $s['net_sales'] * 100, 1).'%' : '—';
@endphp

<x-app-layout title="Estado de resultados">
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Estado de resultados" :description="$period->label().' · ¿cómo me fue en el período?'" />

        <x-ui.report.filters :cols="2">
            <x-ui.forms.input type="date" label="Desde" name="from" :value="$period->from->toDateString()" />
            <x-ui.forms.input type="date" label="Hasta" name="to" :value="$period->to->toDateString()" />
        </x-ui.report.filters>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui.stat label="Ventas netas" :value="$money($s['net_sales'])" hint="Sin ITBIS, menos devoluciones" />
            <x-ui.stat label="Ganancia bruta" :value="$money($s['gross_profit'])" :variant="$s['gross_profit'] < 0 ? 'error' : 'default'"
                :hint="'Margen '.$grossMargin" />
            <x-ui.stat label="Resultado del período" :value="$money($s['result'])" :variant="$s['result'] < 0 ? 'error' : 'success'"
                :hint="$s['margin'] !== null ? 'Margen '.number_format($s['margin'], 1).'%' : 'Sin ventas en el período'" />
        </div>

        <x-ui.infolist.section title="Detalle" icon="heroicon-o-document-chart-bar" :cols="0">
            <x-ui.report.rows>
                <x-ui.report.row label="Ventas" sub="Sin ITBIS: el ITBIS no es ingreso" :value="$money($s['sales'])" />
                <x-ui.report.row label="(−) Devoluciones" :value="$money(-$s['returns'])" :strong="false" variant="error" />
                <x-ui.report.row label="Ventas netas" :value="$money($s['net_sales'])" />
                <x-ui.report.row label="(−) Costo de lo vendido" sub="Al costo de cada producto el día que salió" :value="$money(-$s['cost'])" :strong="false" variant="error" />
                <li class="flex items-center gap-3 py-3 border-t-2 border-gray-200">
                    <span class="flex-1 text-sm font-bold text-gray-900">Ganancia bruta</span>
                    <span class="text-xs text-slate-400">{{ $grossMargin }}</span>
                    <span class="text-sm font-bold tabular-nums text-gray-900">{{ $money($s['gross_profit']) }}</span>
                </li>
                <x-ui.report.row label="(−) Pérdidas de inventario" sub="Mermas, diferencias de tomas físicas y ajustes" :value="$money(-$l['total'])" :strong="false" :variant="$l['total'] > 0 ? 'error' : 'success'" />
                <x-ui.report.row label="(−) Gastos" sub="Gastos registrados, de contado o a crédito" :value="$money(-$s['expenses'])" :strong="false" variant="error" />
                <li class="flex items-center gap-3 py-3 border-t-2 border-gray-900">
                    <span class="flex-1 text-base font-bold text-gray-900">Resultado del período</span>
                    @if($s['margin'] !== null)
                        <span class="text-xs text-slate-400">{{ number_format($s['margin'], 1) }}%</span>
                    @endif
                    <span @class(['text-lg font-bold tabular-nums', 'text-state-success' => $s['result'] >= 0, 'text-state-error' => $s['result'] < 0])>{{ $money($s['result']) }}</span>
                </li>
            </x-ui.report.rows>
            <p class="mt-4 text-xs text-slate-400">Las compras no restan: cambian dinero por mercancía. Lo comprado se vuelve costo cuando se vende.</p>
        </x-ui.infolist.section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Gastos por categoría" icon="heroicon-o-receipt-percent" :cols="0">
                <x-ui.report.rows empty="Sin gastos en el período.">
                    @foreach($s['expenses_by_category'] as $row)
                        <x-ui.report.row :label="$row['label']" :hint="'('.$row['count'].')'" :value="$money($row['value'])" />
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Pérdidas de inventario" icon="heroicon-o-archive-box-x-mark" :cols="0" description="A su costo congelado">
                @if($l['total'] == 0 && $l['count_surplus'] == 0)
                    <x-ui.report.rows empty="Sin pérdidas en el período." />
                @else
                    <x-ui.report.rows>
                        @foreach($l['by_reason'] as $reason => $total)
                            <x-ui.report.row :label="'Merma · '.($reasons[$reason] ?? $reason)" :value="$money($total)" />
                        @endforeach
                        @if($l['count_shortage'] > 0)
                            <x-ui.report.row label="Faltantes de tomas físicas" :value="$money($l['count_shortage'])" />
                        @endif
                        @if($l['count_surplus'] > 0)
                            <x-ui.report.row label="(+) Sobrantes de tomas físicas" sub="Restan a la pérdida" :value="$money(-$l['count_surplus'])" variant="success" />
                        @endif
                        @if($l['adjustments'] != 0)
                            <x-ui.report.row label="Ajustes manuales" sub="Lo que salió menos lo que entró" :value="$money($l['adjustments'])" />
                        @endif
                    </x-ui.report.rows>
                @endif
            </x-ui.infolist.section>
        </div>
    </div>
</x-app-layout>
