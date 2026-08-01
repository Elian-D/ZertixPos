<x-app-layout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        
        {{-- Header & Filtros --}}
        <div class="mb-8 space-y-6">
            
            {{-- Fila 1: Título y Botón Nuevo Asiento --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-shrink-0">
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Centro Contable</h2>
                    <p class="text-sm text-gray-500 mt-1">Monitoreo de salud financiera y rentabilidad</p>
                </div>
                
                <a href="{{ route('accounting.journal_entries.create') }}" 
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 active:translate-y-0 transition-all whitespace-nowrap">
                    <x-heroicon-s-document-plus class="w-4 h-4"/>
                    <span>Nuevo Asiento Manual</span>
                </a>
            </div>

            {{-- Fila 2: Filtros --}}
            <div class="flex flex-col lg:flex-row gap-4">
                
                {{-- Grupo de Filtros Rápidos (Botones) --}}
                <div class="inline-flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    @php $ranges = ['today' => 'Hoy', '7days' => '7D', 'this_month' => 'Este Mes', '30days' => '30D', 'this_year' => 'Este Año']; @endphp
                    <div class="flex gap-1 min-w-max">
                        @foreach($ranges as $key => $label)
                            <a href="{{ route('accounting.dashboard.index', ['range' => $key]) }}" 
                            class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $filters['current_range'] == $key ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Selector de Rango Manual --}}
                <form action="{{ route('accounting.dashboard.index') }}" method="GET" 
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

        {{-- NUEVO: Alertas Financieras --}}
        @if(count($alerts) > 0)
        <div class="mb-8 space-y-4 w-full"> {{-- Cambiado de grid a space-y-4 para que ocupen todo el ancho --}}
            @foreach($alerts as $alert)
                <div class="bg-{{ $alert['color'] }}-50 border border-{{ $alert['color'] }}-200 rounded-xl p-4 flex items-start gap-4 w-full shadow-sm">
                    <div class="flex-shrink-0 mt-0.5">
                        @if($alert['type'] === 'warning')
                            <x-heroicon-s-exclamation-triangle class="w-6 h-6 text-{{ $alert['color'] }}-600"/>
                        @elseif($alert['type'] === 'danger')
                            <x-heroicon-s-exclamation-circle class="w-6 h-6 text-{{ $alert['color'] }}-600"/>
                        @else
                            <x-heroicon-s-check-circle class="w-6 h-6 text-{{ $alert['color'] }}-600"/>
                        @endif
                    </div>
                    <div class="flex-1"> {{-- flex-1 asegura que el texto use el espacio restante --}}
                        <h4 class="text-sm font-bold text-{{ $alert['color'] }}-900">{{ $alert['title'] }}</h4>
                        <p class="text-sm text-{{ $alert['color'] }}-700 mt-1 leading-relaxed">{{ $alert['message'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- KPIs: Ajustado a 3 columnas para que las 6 queden alineadas --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <x-dashboard.kpi-card 
                title="Efectivo Disponible" 
                :value="'$' . number_format($stats['cash_balance'], 2)" 
                icon="banknotes" color="green" 
            />

            <x-dashboard.kpi-card 
                title="Cuentas por Cobrar" 
                :value="'$' . number_format($stats['cxc_balance'], 2)" 
                icon="user-group" color="blue" secondary-text="Pendiente de cobro"
            />

            <x-dashboard.kpi-card 
                title="Patrimonio en Productos" 
                :value="'$' . number_format($stats['inventory_value'], 2)" 
                icon="square-3-stack-3d" color="indigo" secondary-text="Valor en almacenes"
            />

            <x-dashboard.kpi-card 
                title="Utilidad Bruta" 
                :value="'$' . number_format($stats['gross_profit'], 2)" 
                icon="chart-bar" 
                color="{{ $stats['gross_profit'] >= 0 ? 'green' : 'red' }}" 
                :trend="number_format($stats['profit_margin'], 1) . '% Margen'"
            />

            <x-dashboard.kpi-card 
                title="Cuentas por Pagar" 
                :value="'$' . number_format($stats['cxp_balance'], 2)" 
                icon="credit-card" color="red" secondary-text="Deuda a proveedores"
            />

            <x-dashboard.kpi-card 
                title="Ratio de Liquidez" 
                :value="number_format($stats['liquidity_ratio'], 2)" 
                icon="scale" 
                color="{{ $stats['liquidity_ratio'] >= 1 ? 'green' : 'orange' }}" 
                secondary-text="{{ $stats['liquidity_ratio'] >= 1 ? 'Saludable' : 'Revisar flujo' }}"
            />
        </div>

        {{-- Gráficos: NUEVO Layout con Flujo de Caja --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            {{-- Rendimiento Operativo --}}
            <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Rendimiento Operativo</h3>
                <div id="chart-performance" class="min-h-[350px] w-full"></div>
            </div>

            {{-- Composición de Activos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-6 uppercase tracking-wider">Composición de Activos</h3>
                <div id="chart-composition" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- NUEVO: Fila de Gráficos Adicionales --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            {{-- Flujo de Caja (Ingresos vs Egresos) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Flujo de Caja</h3>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $stats['cash_flow'] >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $stats['cash_flow'] >= 0 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        {{ $stats['cash_flow'] >= 0 ? 'Positivo' : 'Negativo' }}
                    </span>
                </div>
                <div id="chart-cashflow" class="min-h-[300px] w-full"></div>
            </div>

            {{-- Distribución de Gastos Operativos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-6 uppercase tracking-wider">Gastos Operativos</h3>
                <div id="chart-expenses" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- Tabla de Movimientos Recientes --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Últimos Movimientos Contables</h3>
                <span class="px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">{{ $filters['current_range'] === 'custom' ? 'Personalizado' : 'Global' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold tracking-widest">
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Referencia</th>
                            <th class="px-6 py-3">Descripción / Concepto</th>
                            <th class="px-6 py-3 text-right">Monto</th>
                            <th class="px-6 py-3 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentEntries as $entry)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-600 font-medium">{{ $entry->created_at->format('d M, Y') }}</div>
                                <div class="text-[11px] text-gray-400">{{ $entry->created_at->format('H:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">
                                    {{ $entry->reference }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 font-medium">
                                {{ $entry->description }}
                                <div class="text-[11px] text-gray-400 font-normal">Registrado por {{ $entry->creator->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-gray-900">${{ number_format($entry->total_debit ?? 0, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold {{ $entry->status === 'posted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ strtoupper($entry->status) }}
                                </span>
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
            let perfChart, compChart, cashflowChart, expensesChart;

            setTimeout(() => {
                const chartsData = {
                    performance: {
                        labels: {!! json_encode($charts['performance']['labels']) !!},
                        income: {!! json_encode($charts['performance']['income']) !!},
                        costs: {!! json_encode($charts['performance']['costs']) !!}
                    },
                    composition: {
                        labels: {!! json_encode($charts['composition']['labels']) !!},
                        values: {!! json_encode($charts['composition']['values']) !!}
                    },
                    cashflow: {
                        labels: {!! json_encode($charts['cashflow']['labels']) !!},
                        inflows: {!! json_encode($charts['cashflow']['inflows']) !!},
                        outflows: {!! json_encode($charts['cashflow']['outflows']) !!}
                    },
                    expenses: {
                        labels: {!! json_encode($charts['expenses']['labels']) !!},
                        values: {!! json_encode($charts['expenses']['values']) !!}
                    }
                };

                // 1. Gráfico de Rendimiento
                const perfEl = document.querySelector("#chart-performance");
                if (perfEl) {
                    perfChart = new ApexCharts(perfEl, {
                        series: [
                            { name: 'Ingresos', data: chartsData.performance.income.map(Number) },
                            { name: 'Costo de Ventas', data: chartsData.performance.costs.map(Number) }
                        ],
                        chart: { type: 'bar', height: 350, toolbar: { show: false }, redrawOnParentResize: true },
                        colors: ['#10B981', '#6366F1'],
                        plotOptions: { bar: { columnWidth: '60%' } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: chartsData.performance.labels },
                        yaxis: { labels: { formatter: (val) => '$' + val.toLocaleString() } },
                        legend: { position: 'top' }
                    });
                    perfChart.render();
                }

                // 2. Gráfico de Composición
                const compEl = document.querySelector("#chart-composition");
                if (compEl) {
                    compChart = new ApexCharts(compEl, {
                        series: chartsData.composition.values.map(Number),
                        chart: { type: 'donut', height: 320, redrawOnParentResize: true },
                        labels: chartsData.composition.labels,
                        colors: ['#10B981', '#3B82F6', '#6366F1'],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '70%' } } }
                    });
                    compChart.render();
                }

                // 3. NUEVO: Gráfico de Flujo de Caja
                const cashflowEl = document.querySelector("#chart-cashflow");
                if (cashflowEl) {
                    cashflowChart = new ApexCharts(cashflowEl, {
                        series: [
                            { name: 'Ingresos', data: chartsData.cashflow.inflows.map(Number) },
                            { name: 'Egresos', data: chartsData.cashflow.outflows.map(Number) }
                        ],
                        chart: { type: 'area', height: 300, toolbar: { show: false }, stacked: false },
                        colors: ['#10B981', '#EF4444'],
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: chartsData.cashflow.labels },
                        yaxis: { labels: { formatter: (val) => '$' + val.toLocaleString() } },
                        legend: { position: 'top' }
                    });
                    cashflowChart.render();
                }

                // 4. NUEVO: Gráfico de Gastos Operativos
                const expensesEl = document.querySelector("#chart-expenses");
                if (expensesEl) {
                    expensesChart = new ApexCharts(expensesEl, {
                        series: chartsData.expenses.values.map(Number),
                        chart: { type: 'donut', height: 320 },
                        labels: chartsData.expenses.labels,
                        colors: ['#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6'],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '65%' } } },
                        dataLabels: { 
                            enabled: true,
                            formatter: function(val) {
                                return val.toFixed(1) + '%';
                            }
                        }
                    });
                    expensesChart.render();
                }
            }, 150);

            // Manejo de resize
            window.addEventListener('resize-charts', () => {
                if(perfChart) perfChart.windowResizeHandler();
                if(compChart) compChart.windowResizeHandler();
                if(cashflowChart) cashflowChart.windowResizeHandler();
                if(expensesChart) expensesChart.windowResizeHandler();
            });
        });
    </script>
    @endpush
</x-app-layout>