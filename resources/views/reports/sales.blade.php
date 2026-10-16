{{-- Reporte de Ventas (v1.5.0 Fase 6): totales, ventas por día, métodos de pago, productos y
     clientes del período. Recibe $period, $sales, $returns, $byDay, $byMethod, $topProducts,
     $topClients y $recent (App\Http\Controllers\Reports\SalesReportController). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '−' : '').$currency.number_format(abs((float) $v), 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $bars = $byDay->map(fn ($d) => [
        'label' => $d['date']->format('j'),
        'value' => $d['value'],
        'tooltip' => $d['date']->translatedFormat('j M').' · '.$money($d['value']),
    ])->all();
    $methodTotal = max($byMethod->sum('value'), 0.01);
@endphp

<x-app-layout title="Reporte de Ventas">
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Reporte de Ventas" :description="$period->label().' · solo ventas completadas'" />

        <x-ui.report.filters :cols="2">
            <x-ui.forms.input type="date" label="Desde" name="from" :value="$period->from->toDateString()" />
            <x-ui.forms.input type="date" label="Hasta" name="to" :value="$period->to->toDateString()" />
        </x-ui.report.filters>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-ui.stat label="Total vendido" :value="$money($sales['gross'])"
                :hint="$returns > 0 ? 'Con ITBIS · devuelto '.$money($returns).' sin ITBIS' : 'Con ITBIS'" />
            <x-ui.stat label="Transacciones" :value="$sales['count']" :hint="'Contado '.$money($sales['cash']).' · crédito '.$money($sales['credit'])" />
            <x-ui.stat label="Ticket promedio" :value="$money($sales['average'])" />
            <x-ui.stat label="ITBIS cobrado" :value="$money($sales['tax'])" :hint="'Neto sin ITBIS '.$money($sales['net'])" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <x-ui.infolist.section title="Ventas por día" icon="heroicon-o-chart-bar" :cols="0" class="lg:col-span-2">
                <x-ui.report.bar-chart :bars="$bars" />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Por método de pago" icon="heroicon-o-credit-card" :cols="0">
                <x-ui.report.rows empty="Sin ventas en el período.">
                    @foreach($byMethod as $method)
                        <li class="py-2.5">
                            <div class="flex items-center gap-3">
                                <span class="flex-1 text-sm text-gray-800">{{ $method['label'] }} <span class="text-xs text-slate-400">({{ $method['count'] }})</span></span>
                                <span class="text-sm font-semibold tabular-nums text-gray-900">{{ $money($method['value']) }}</span>
                            </div>
                            <x-ui.report.progress class="mt-1.5" :value="$method['value'] / $methodTotal * 100" />
                        </li>
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Productos más vendidos" icon="heroicon-o-trophy" :cols="0" description="Top 10 por monto, con ITBIS">
                <x-ui.report.rows empty="Sin ventas en el período.">
                    @foreach($topProducts as $product)
                        <x-ui.report.row :label="$product->name" :hint="'×'.$qty($product->qty)" :value="$money($product->total)" />
                    @endforeach
                </x-ui.report.rows>
            </x-ui.infolist.section>

            <div class="flex flex-col gap-6">
                <x-ui.infolist.section title="Mejores clientes" icon="heroicon-o-user-group" :cols="0" description="Sin Consumidor Final">
                    <x-ui.report.rows empty="Solo hubo ventas a Consumidor Final.">
                        @foreach($topClients as $client)
                            <x-ui.report.row :label="$client->name" :hint="$client->count.' '.($client->count == 1 ? 'venta' : 'ventas')" :value="$money($client->total)" />
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Últimas ventas" icon="heroicon-o-receipt-percent" :cols="0">
                    <x-slot:headerActions>
                        <x-ui.button href="{{ route('sales.index') }}" size="sm" variant="secondary" appearance="ghost">Ver todas</x-ui.button>
                    </x-slot:headerActions>
                    <x-ui.report.rows empty="Sin ventas en el período.">
                        @foreach($recent as $sale)
                            <x-ui.report.row :label="$sale->number.' · '.($sale->client->name ?? 'Cliente')"
                                :sub="$sale->sale_date->format('d/m/Y h:i A').' · '.($sale->payment_type === 'credit' ? 'Crédito' : 'Contado')"
                                :value="$money((float) $sale->net_amount + (float) $sale->tax_amount)" :href="route('sales.show', $sale)" />
                        @endforeach
                    </x-ui.report.rows>
                </x-ui.infolist.section>
            </div>
        </div>
    </div>
</x-app-layout>
