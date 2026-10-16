{{-- Inicio (v1.5.0 REQ-6.5) — centro operativo: onboarding, hoy, el mes, ventas por día con
     stock bajo, y lo que vence. Cada bloque solo existe si su clave de $can es true (permiso
     y módulo, ver App\Http\Controllers\DashboardController). Diseño de reportes: x-ui.stat,
     x-ui.infolist.section y x-ui.report.* (docs/ui/reports.md). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '−' : '').$currency.number_format(abs((float) $v), 2);
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();

    $showToday = $can['sales'] || $can['sessions'] || $can['accounts'];
    $showMonth = $can['sales'] || $can['receivables'] || $can['payables'];

    if ($can['sales']) {
        $prev = $prevSales['gross'];
        $change = $prev > 0 ? round(($monthSales['gross'] - $prev) / $prev * 100, 1) : null;
        $bars = $salesByDay->map(fn ($d) => [
            'label' => $d['date']->format('j'),
            'value' => $d['value'],
            'tooltip' => $d['date']->translatedFormat('j M').' · '.$money($d['value']),
        ])->all();
    }
    $accountsTotal = $can['accounts'] ? $accounts->sum('current_balance') : 0;
@endphp

<x-app-layout title="Inicio">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$greeting.', '.$firstName"
            :description="ucfirst(now()->translatedFormat('l, j \d\e F \d\e Y'))" />

        <livewire:onboarding.onboarding-checklist />

        {{-- 2. Hoy --}}
        @if($showToday)
            <section class="flex flex-col gap-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Hoy</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @if($can['sales'])
                        <x-ui.stat label="Ventas de hoy" :value="$money($todaySales['gross'])"
                            :hint="$todaySales['count'].' '.($todaySales['count'] === 1 ? 'transacción' : 'transacciones').($todaySales['count'] ? ' · ticket '.$money($todaySales['average']) : '')"
                            :href="route('reports.sales', ['from' => now()->toDateString(), 'to' => now()->toDateString()])" />
                    @endif
                    @if($can['sessions'])
                        <x-ui.stat label="Turnos abiertos" :value="$openSessions->count()"
                            :href="route('sales.pos.sessions.index')">
                            {{ $openSessions->isEmpty() ? 'Ninguna caja abierta' : $openSessions->map(fn ($s) => ($s->terminal->name ?? 'TPV').' ('.($s->user->name ?? '—').')')->join(', ') }}
                        </x-ui.stat>
                    @endif
                    @if($can['accounts'])
                        <x-ui.stat label="En Cajas y Bancos" :value="$money($accountsTotal)" :variant="$accountsTotal < 0 ? 'error' : 'default'"
                            :hint="$accounts->count().' '.($accounts->count() === 1 ? 'cuenta' : 'cuentas').' · sin el efectivo de los turnos'"
                            :href="route('finance.treasury.index')" />
                    @endif
                </div>
            </section>
        @endif

        {{-- 3. El mes --}}
        @if($showMonth)
            <section class="flex flex-col gap-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ ucfirst(now()->translatedFormat('F')) }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    @if($can['sales'])
                        <x-ui.stat label="Ventas del mes" :value="$money($monthSales['gross'])" :href="route('reports.sales')">
                            @if($change === null)
                                Sin ventas el mes anterior a esta fecha
                            @else
                                <span @class(['font-semibold', 'text-state-success' => $change >= 0, 'text-state-error' => $change < 0])>
                                    {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 1) }}%
                                </span>
                                contra el mismo período del mes anterior
                            @endif
                        </x-ui.stat>
                    @endif
                    @if($can['profit'])
                        <x-ui.stat label="Ganancia bruta" :value="$money($grossProfit)" :variant="$grossProfit < 0 ? 'error' : 'success'"
                            hint="Ventas sin ITBIS − costo de lo vendido" :href="route('reports.results')" />
                    @endif
                    @if($can['receivables'])
                        <x-ui.stat label="Por cobrar" :value="$money($receivablesTotal)"
                            hint="Lo que te deben los clientes" :href="route('finance.receivables.index')" />
                    @endif
                    @if($can['payables'])
                        <x-ui.stat label="Por pagar" :value="$money($payablesTotal)" :variant="$payablesTotal > 0 ? 'error' : 'default'"
                            hint="Lo que debes a proveedores" :href="route('finance.payables.index')" />
                    @endif
                </div>
            </section>
        @endif

        {{-- 4. Ventas por día y stock bajo --}}
        @if($can['sales'] || $can['stock'])
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                @if($can['sales'])
                    <x-ui.infolist.section title="Ventas por día" icon="heroicon-o-chart-bar" :cols="0"
                        :description="'Del 1 al '.now()->day.' de '.now()->translatedFormat('F')" @class(['lg:col-span-2' => $can['stock'], 'lg:col-span-3' => ! $can['stock']])>
                        <x-ui.report.bar-chart :bars="$bars" :highlight="count($bars) - 1" empty="Todavía no hay ventas este mes." />
                    </x-ui.infolist.section>
                @endif

                @if($can['stock'])
                    <x-ui.infolist.section title="Stock bajo" icon="heroicon-o-exclamation-triangle" :cols="0"
                        :description="$lowStockCount ? $lowStockCount.' '.($lowStockCount === 1 ? 'existencia' : 'existencias').' en o bajo el mínimo' : null"
                        @class(['lg:col-span-1' => $can['sales'], 'lg:col-span-3' => ! $can['sales']])>
                        <x-ui.report.rows empty="Todo está sobre el mínimo.">
                            @foreach($lowStock as $stock)
                                <x-ui.report.row :label="$stock->product->name ?? '—'" :sub="$stock->warehouse->name ?? null"
                                    :hint="rtrim(rtrim(number_format((float) $stock->quantity, 2), '0'), '.').' / '.rtrim(rtrim(number_format((float) $stock->min_stock, 2), '0'), '.')">
                                    <x-slot:badge>
                                        <x-ui.badge :variant="(float) $stock->quantity <= 0 ? 'error' : 'warning'" size="sm" :dot="false">
                                            {{ (float) $stock->quantity <= 0 ? 'Agotado' : 'Bajo' }}
                                        </x-ui.badge>
                                    </x-slot:badge>
                                </x-ui.report.row>
                            @endforeach
                        </x-ui.report.rows>
                        @if($can['order'] && $lowStock->isNotEmpty())
                            <x-ui.button href="{{ route('purchases.orders.create') }}" size="sm" variant="secondary" appearance="outline"
                                iconLeft="heroicon-s-plus" class="mt-4 w-full justify-center">
                                Crear orden de compra
                            </x-ui.button>
                        @endif
                    </x-ui.infolist.section>
                @endif
            </div>
        @endif

        {{-- 5. Lo que vence --}}
        @if($can['receivables'] || $can['payables'])
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                @if($can['receivables'])
                    <x-ui.infolist.section title="Me deben" icon="heroicon-o-arrow-down-left" :cols="0"
                        description="Cuentas por cobrar vencidas o más próximas a vencer">
                        <x-slot:headerActions>
                            <x-ui.button href="{{ route('finance.receivables.index') }}" size="sm" variant="secondary" appearance="ghost">Ver todas</x-ui.button>
                        </x-slot:headerActions>
                        <x-ui.report.rows empty="Nadie te debe.">
                            @foreach($receivables as $receivable)
                                <x-ui.report.row :label="$receivable->client->name ?? 'Cliente'"
                                    :sub="$receivable->number.' · '.($receivable->is_overdue ? 'venció' : 'vence').' '.$receivable->due_date->format('d/m/Y')"
                                    :value="$money($receivable->current_balance)">
                                    <x-slot:badge>
                                        @if($receivable->is_overdue)
                                            <x-ui.badge variant="error" size="sm" :dot="false">Vencida</x-ui.badge>
                                        @endif
                                    </x-slot:badge>
                                </x-ui.report.row>
                            @endforeach
                        </x-ui.report.rows>
                    </x-ui.infolist.section>
                @endif

                @if($can['payables'])
                    <x-ui.infolist.section title="Debo" icon="heroicon-o-arrow-up-right" :cols="0"
                        description="Cuentas por pagar vencidas o que vencen en los próximos 7 días">
                        <x-slot:headerActions>
                            <x-ui.button href="{{ route('finance.payables.index') }}" size="sm" variant="secondary" appearance="ghost">Ver todas</x-ui.button>
                        </x-slot:headerActions>
                        <x-ui.report.rows empty="Nada vence en los próximos 7 días.">
                            @foreach($payables as $payable)
                                <x-ui.report.row :label="$payable->vendor?->display_name ?? $payable->concept"
                                    :sub="$payable->number.' · '.($payable->isOverdue() ? 'venció' : 'vence').' '.$payable->due_date->format('d/m/Y')"
                                    :value="$money($payable->balance)" :href="route('finance.payables.show', $payable)">
                                    <x-slot:badge>
                                        @if($payable->isOverdue())
                                            <x-ui.badge variant="error" size="sm" :dot="false">Vencida</x-ui.badge>
                                        @endif
                                    </x-slot:badge>
                                </x-ui.report.row>
                            @endforeach
                        </x-ui.report.rows>
                    </x-ui.infolist.section>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
