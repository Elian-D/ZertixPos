<x-app-layout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        
            {{-- Header & Filtros Rápidos --}}
            <div class="mb-8 space-y-6">
                
                {{-- Fila 1: Título y Botón Nueva Venta --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex-shrink-0">
                        <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Panel de Ventas</h2>
                        <p class="text-sm text-gray-500 mt-1">Análisis operativo y rendimiento comercial</p>
                    </div>
                    
                    {{-- Botón Nueva Venta (siempre visible) --}}
                    <x-ui.button href="{{ route('sales.create') }}" variant="primary" :hoverEffect="true" iconLeft="heroicon-s-shopping-cart" class="whitespace-nowrap">
                        Nueva Venta
                    </x-ui.button>
                </div>

                {{-- Fila 2: Filtros --}}
                <div class="flex flex-col lg:flex-row gap-4">
                    
                    {{-- Grupo de Filtros Rápidos (Botones) --}}
                    <div class="inline-flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                        @php $ranges = ['today' => 'Hoy', '7days' => '7D', 'this_month' => 'Este Mes', '30days' => '30D']; @endphp
                        <div class="flex gap-1 min-w-max">
                            @foreach($ranges as $key => $label)
                                <a href="{{ route('reports.sales', ['range' => $key]) }}" 
                                class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $filters['current_range'] == $key ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Selector de Rango Manual --}}
                    <form action="{{ route('reports.sales') }}" method="GET" 
                        class="flex items-center bg-white border border-gray-200 rounded-xl p-1 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all min-w-max">
                        <input type="hidden" name="range" value="custom">
                        <div class="flex items-center px-2 gap-1">
                            <input type="date" name="start_date" value="{{ $filters['start'] }}" 
                                class="text-xs border-none focus:ring-0 p-1 text-gray-600 bg-transparent w-[110px]">
                            <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest flex-shrink-0">al</span>
                            <input type="date" name="end_date" value="{{ $filters['end'] }}" 
                                class="text-xs border-none focus:ring-0 p-1 text-gray-600 bg-transparent w-[110px]">
                        </div>
                        <button type="submit" 
                                class="p-2 bg-gray-50 hover:bg-indigo-50 text-indigo-600 rounded-lg transition-colors border-l border-gray-100 flex-shrink-0">
                            <x-heroicon-s-magnifying-glass class="w-4 h-4"/>
                        </button>
                    </form>
                </div>
            </div>


        {{-- KPIs de Ventas --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <x-dashboard.kpi-card 
                title="Ingresos Totales" 
                :value="config('regional.currency_symbol') . number_format($stats['total_revenue'], 2)" 
                icon="banknotes" color="green" 
                :trend="config('regional.currency_symbol') . number_format($stats['avg_ticket'], 2) . ' Promedio'"
            />

            <x-dashboard.kpi-card 
                title="Ventas Realizadas" 
                :value="number_format($stats['total_count'])" 
                icon="shopping-bag" color="indigo" secondary-text="Transacciones en el periodo"
            />

            <x-dashboard.kpi-card 
                title="Efectividad de Cobro" 
                :value="config('regional.currency_symbol') . number_format($stats['collected'], 2)" 
                icon="check-badge" color="blue" 
                secondary-text="Pagos aplicados a facturas"
            />

            <x-dashboard.kpi-card 
                title="Ventas a Crédito" 
                :value="config('regional.currency_symbol') . number_format($stats['credit_total'], 2)" 
                icon="clock" color="orange" 
                secondary-text="Pendiente por ingresar a caja"
            />

            <x-dashboard.kpi-card 
                title="Ventas al Contado" 
                :value="config('regional.currency_symbol') . number_format($stats['cash_total'], 2)" 
                icon="currency-dollar" color="green" 
                secondary-text="Ingreso inmediato"
            />

            <x-dashboard.kpi-card 
                title="Ticket Promedio" 
                :value="config('regional.currency_symbol') . number_format($stats['avg_ticket'], 2)" 
                icon="presentation-chart-line" color="blue" 
                secondary-text="Valor medio por transacción"
            />
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            {{-- Línea de tiempo de ventas --}}
            <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Tendencia de Ventas</h3>
                <div id="chart-sales-timeline" class="min-h-[350px] w-full"></div>
            </div>

            {{-- Distribución de Métodos de Pago --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-6 uppercase tracking-wider">Métodos de Pago</h3>
                <div id="chart-payment-methods" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- Rankings: Clientes y Productos --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            {{-- Top Clientes --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Top 5 Clientes</h3>
                    <x-heroicon-s-user-group class="w-5 h-5 text-gray-400"/>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($topClients as $client)
                        <div class="py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-600 font-medium">{{ $client->name }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ config('regional.currency_symbol') }}{{ number_format($client->total, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top Productos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Productos más vendidos</h3>
                    <x-heroicon-s-fire class="w-5 h-5 text-orange-500"/>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($topProducts as $product)
                        <div class="py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-600 font-medium">{{ $product->name }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700">
                                {{ number_format($product->qty, 0) }} unds
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ventas Recientes --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Últimas Facturas Emitidas</h3>
                <a href="{{ route('finance.invoices.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">Ver todas →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold tracking-widest">
                            <th class="px-6 py-3">Factura / Fecha</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3">Método / Tipo</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentSales as $sale)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-indigo-600">{{ $sale->invoice_number }}</div>
                                <div class="text-[11px] text-gray-400">{{ $sale->sale_date->format('d M, H:i A') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 font-medium">{{ $sale->client->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($sale->payment_type === 'credit')
                                    <x-ui.badge variant="warning" icon="heroicon-s-clock" size="sm">
                                        CRÉDITO
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="success" size="sm">
                                        {{ $sale->tipoPago->nombre ?? 'Contado' }}
                                    </x-ui.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">
                                {{ config('regional.currency_symbol') }}{{ number_format($sale->grand_total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.badge :variant="$sale->status === 'completed' ? 'success' : 'error'" :dot="false" size="sm">
                                    {{ strtoupper($sale->status) }}
                                </x-ui.badge>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let timelineChart, methodsChart;

            setTimeout(() => {
                const data = {
                    timeline: {
                        labels: {!! json_encode($charts['timeline']['labels']) !!},
                        values: {!! json_encode($charts['timeline']['values']) !!}
                    },
                    methods: {
                        labels: {!! json_encode($charts['methods']['labels']) !!},
                        values: {!! json_encode($charts['methods']['values']) !!}
                    }
                };

                // 1. Gráfico de Tendencia (Timeline)
                const timelineEl = document.querySelector("#chart-sales-timeline");
                if (timelineEl) {
                    timelineChart = new ApexCharts(timelineEl, {
                        series: [{ name: 'Ventas ({{ config('regional.currency_symbol') }})', data: data.timeline.values.map(Number) }],
                        chart: { type: 'area', height: 350, toolbar: { show: false }, zoom: { enabled: false } },
                        colors: ['#6366F1'],
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: data.timeline.labels },
                        yaxis: { labels: { formatter: (val) => '{{ config('regional.currency_symbol') }}' + val.toLocaleString() } }
                    });
                    timelineChart.render();
                }

                // 2. Gráfico de Métodos de Pago
                const methodsEl = document.querySelector("#chart-payment-methods");
                if (methodsEl) {
                    methodsChart = new ApexCharts(methodsEl, {
                        series: data.methods.values.map(Number),
                        chart: { type: 'donut', height: 320 },
                        labels: data.methods.labels,
                        colors: ['#10B981', '#6366F1', '#F59E0B', '#3B82F6'],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '70%' } } }
                    });
                    methodsChart.render();
                }
            }, 150);

            window.addEventListener('resize-charts', () => {
                if(timelineChart) timelineChart.windowResizeHandler();
                if(methodsChart) methodsChart.windowResizeHandler();
            });
        });
    </script>
    @endpush
</x-app-layout>