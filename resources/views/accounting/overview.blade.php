<x-app-layout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">

        {{-- Header & Filtros --}}
        <div class="mb-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-shrink-0">
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Ingresos y Gastos</h2>
                    <p class="text-sm text-gray-500 mt-1">Ingresos, costos y cobros — sin depender de un asiento contable</p>
                </div>
            </div>

            @php $ranges = ['today' => 'Hoy', '7days' => '7D', 'this_month' => 'Este Mes', '30days' => '30D', 'this_year' => 'Este Año']; @endphp

            <div x-data="{ filterSheetOpen: false }" class="flex flex-col lg:flex-row gap-4">

                {{-- Disparador — solo móvil --}}
                <button type="button" @click="filterSheetOpen = true"
                        class="sm:hidden w-full flex items-center justify-between gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2.5 shadow-sm">
                    <span class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <x-heroicon-s-calendar-days class="w-4 h-4 text-zertix-primary-600"/>
                        {{ $filters['current_range'] === 'custom' ? $filters['start'].' al '.$filters['end'] : ($ranges[$filters['current_range']] ?? 'Filtrar') }}
                    </span>
                    <x-heroicon-s-chevron-down class="w-4 h-4 text-gray-400"/>
                </button>

                {{-- Filtros — desde sm hacia arriba --}}
                <div class="hidden sm:inline-flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    <div class="flex gap-1 min-w-max">
                        @foreach($ranges as $key => $label)
                            <a href="{{ route('finance.overview.index', ['range' => $key]) }}"
                               class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $filters['current_range'] == $key ? 'bg-white text-zertix-primary-600 shadow-sm ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <form action="{{ route('finance.overview.index') }}" method="GET"
                      class="hidden sm:flex items-center bg-white border border-gray-200 rounded-xl p-1 shadow-sm focus-within:ring-2 focus-within:ring-zertix-primary-500/20 transition-all min-w-max">
                    <input type="hidden" name="range" value="custom">
                    <div class="flex items-center px-2 gap-1">
                        <input type="date" name="start_date" value="{{ $filters['start'] }}"
                               class="text-xs border-none focus:ring-0 p-1 text-gray-600 bg-transparent w-[110px]">
                        <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest flex-shrink-0">al</span>
                        <input type="date" name="end_date" value="{{ $filters['end'] }}"
                               class="text-xs border-none focus:ring-0 p-1 text-gray-600 bg-transparent w-[110px]">
                    </div>
                    <button type="submit"
                            class="p-2 bg-gray-50 hover:bg-zertix-primary-50 text-zertix-primary-600 rounded-lg transition-colors border-l border-gray-100 flex-shrink-0">
                        <x-heroicon-s-magnifying-glass class="w-4 h-4"/>
                    </button>
                </form>

                {{-- Bottom sheet — solo móvil --}}
                <div x-show="filterSheetOpen" x-cloak class="sm:hidden fixed inset-0 z-50">
                    <div x-show="filterSheetOpen"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         @click="filterSheetOpen = false" class="absolute inset-0 bg-black/40"></div>

                    <div x-show="filterSheetOpen"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                         class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-2xl p-5 pb-8 max-h-[85vh] overflow-y-auto">

                        <div class="w-10 h-1.5 bg-gray-200 rounded-full mx-auto mb-5"></div>

                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Filtrar Periodo</h4>
                            <button type="button" @click="filterSheetOpen = false" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-s-x-mark class="w-5 h-5"/>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mb-6">
                            @foreach($ranges as $key => $label)
                                <a href="{{ route('finance.overview.index', ['range' => $key]) }}"
                                   class="px-4 py-3 text-sm font-semibold rounded-lg text-center transition-colors {{ $filters['current_range'] == $key ? 'bg-zertix-primary-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-px bg-gray-100 flex-1"></div>
                            <span class="text-[10px] font-bold uppercase text-gray-400 tracking-wide">o personalizado</span>
                            <div class="h-px bg-gray-100 flex-1"></div>
                        </div>

                        <form action="{{ route('finance.overview.index') }}" method="GET" class="space-y-3">
                            <input type="hidden" name="range" value="custom">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 mb-1 block">Desde</label>
                                <input type="date" name="start_date" value="{{ $filters['start'] }}"
                                       class="w-full border border-gray-200 rounded-lg p-2.5 text-sm text-gray-700">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 mb-1 block">Hasta</label>
                                <input type="date" name="end_date" value="{{ $filters['end'] }}"
                                       class="w-full border border-gray-200 rounded-lg p-2.5 text-sm text-gray-700">
                            </div>
                            <button type="submit" class="w-full bg-zertix-primary-600 hover:bg-zertix-primary-700 text-white font-bold text-sm rounded-lg py-3 transition-colors">
                                Aplicar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIs principales --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ingresos del Periodo</p>
                    <div class="p-2 rounded-lg bg-emerald-50">
                        <x-heroicon-o-banknotes class="w-5 h-5 text-emerald-600"/>
                    </div>
                </div>
                <p class="mt-2 text-4xl font-bold text-gray-900 tabular-nums">{{ config('regional.currency_symbol') }}{{ number_format($stats['revenue'], 2) }}</p>
                <p class="mt-1.5 text-xs text-gray-400">{{ number_format($stats['sales_count']) }} ventas completadas</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Utilidad Bruta</p>
                    <div class="p-2 rounded-lg {{ $stats['gross_profit'] >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                        <x-heroicon-o-chart-bar class="w-5 h-5 {{ $stats['gross_profit'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}"/>
                    </div>
                </div>
                <p class="mt-2 text-4xl font-bold text-gray-900 tabular-nums">{{ config('regional.currency_symbol') }}{{ number_format($stats['gross_profit'], 2) }}</p>
                <x-ui.badge :variant="$stats['gross_profit'] >= 0 ? 'success' : 'error'" size="sm" :dot="false" class="mt-1.5">
                    {{ number_format($stats['margin'], 1) }}% margen
                </x-ui.badge>
            </div>
        </div>

        {{-- KPIs secundarios --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Costo de Ventas</p>
                    <div class="p-1.5 rounded-md bg-rose-50">
                        <x-heroicon-o-cube class="w-4 h-4 text-rose-500"/>
                    </div>
                </div>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ config('regional.currency_symbol') }}{{ number_format($stats['cost_of_sales'], 2) }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Salidas de inventario al costo actual</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Cobrado en el Periodo</p>
                    <div class="p-1.5 rounded-md bg-blue-50">
                        <x-heroicon-o-check-badge class="w-4 h-4 text-blue-500"/>
                    </div>
                </div>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ config('regional.currency_symbol') }}{{ number_format($stats['collected'], 2) }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Abonos aplicados a facturas</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Por Cobrar (CxC)</p>
                    <div class="p-1.5 rounded-md bg-amber-50">
                        <x-heroicon-o-clock class="w-4 h-4 text-amber-500"/>
                    </div>
                </div>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ config('regional.currency_symbol') }}{{ number_format($stats['receivable_pending'], 2) }}</p>
                <p class="mt-1 text-[11px] text-gray-400">Saldo pendiente, todas las facturas</p>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Ingresos vs. Costo de Ventas</h3>
                <div id="chart-revenue-cost" class="min-h-[350px] w-full"></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-6 uppercase tracking-wider">Contado vs Crédito</h3>
                <div id="chart-composition" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- Cuentas por Cobrar Pendientes --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Cuentas por Cobrar Pendientes</h3>
                <a href="{{ route('finance.receivables.index') }}" class="text-xs font-semibold text-zertix-primary-600 hover:text-zertix-primary-800 transition">Ver todas →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 text-[11px] font-semibold uppercase tracking-wide border-b border-gray-200">
                            <th class="px-6 py-2.5">Documento</th>
                            <th class="px-6 py-2.5">Cliente</th>
                            <th class="px-6 py-2.5">Vencimiento</th>
                            <th class="px-6 py-2.5 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($topReceivables as $receivable)
                            @php $isOverdue = \Carbon\Carbon::parse($receivable->due_date)->isPast(); @endphp
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="font-mono text-xs font-medium text-gray-700">
                                        {{ $receivable->document_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-800">
                                    {{ $receivable->client->name ?? 'Cliente eliminado' }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $isOverdue ? 'text-red-600' : 'text-gray-500' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $isOverdue ? 'bg-red-500' : 'bg-gray-300' }}"></span>
                                        {{ \Carbon\Carbon::parse($receivable->due_date)->format('d M, Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold tabular-nums {{ $isOverdue ? 'text-red-700' : 'text-gray-900' }}">
                                    {{ config('regional.currency_symbol') }}{{ number_format($receivable->current_balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-12 text-gray-400">
                                    <x-heroicon-o-check-circle class="w-10 h-10 mx-auto text-gray-200 mb-2"/>
                                    Sin cuentas por cobrar pendientes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let trendChart, compChart;

            setTimeout(() => {
                const trend = {
                    labels: {!! json_encode($charts['timeline']['labels']) !!},
                    revenue: {!! json_encode($charts['timeline']['revenue']) !!},
                    cost: {!! json_encode($charts['timeline']['cost']) !!}
                };
                const composition = {
                    labels: {!! json_encode($charts['composition']['labels']) !!},
                    values: {!! json_encode($charts['composition']['values']) !!}
                };

                const trendEl = document.querySelector("#chart-revenue-cost");
                if (trendEl) {
                    trendChart = new ApexCharts(trendEl, {
                        series: [
                            { name: 'Ingresos', data: trend.revenue.map(Number) },
                            { name: 'Costo de Ventas', data: trend.cost.map(Number) }
                        ],
                        chart: { type: 'bar', height: 350, toolbar: { show: false }, redrawOnParentResize: true },
                        colors: ['#10B981', '#EF4444'],
                        plotOptions: { bar: { columnWidth: '60%' } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: trend.labels },
                        yaxis: { labels: { formatter: (val) => '{{ config('regional.currency_symbol') }}' + val.toLocaleString() } },
                        legend: { position: 'top' }
                    });
                    trendChart.render();
                }

                const compEl = document.querySelector("#chart-composition");
                if (compEl) {
                    compChart = new ApexCharts(compEl, {
                        series: composition.values.map(Number),
                        chart: { type: 'donut', height: 320, redrawOnParentResize: true },
                        labels: composition.labels,
                        colors: ['#10B981', '#F59E0B'],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '70%' } } }
                    });
                    compChart.render();
                }
            }, 150);

            window.addEventListener('resize-charts', () => {
                if (trendChart) trendChart.windowResizeHandler();
                if (compChart) compChart.windowResizeHandler();
            });
        });
    </script>
    @endpush
</x-app-layout>
