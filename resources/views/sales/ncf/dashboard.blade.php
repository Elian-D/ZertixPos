<x-app-layout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        
        {{-- Header & Filtros --}}
        <div class="mb-8 space-y-6">
            
            {{-- Fila 1: Título y Acciones --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-shrink-0">
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Control de NCF</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestión de Números de Comprobantes Fiscales DGII</p>
                </div>
                
                {{-- Botones de Acción --}}
                <div class="flex gap-3 flex-wrap">
                    <x-ui.button href="{{ route('finance.ncf.sequences.index') }}" variant="primary" :hoverEffect="true" iconLeft="heroicon-s-plus-circle" class="whitespace-nowrap">
                        Nueva Secuencia
                    </x-ui.button>

                    <x-ui.button href="{{ route('finance.ncf.logs.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-document-chart-bar" class="whitespace-nowrap">
                        Reporte 607
                    </x-ui.button>
                </div>
            </div>

            {{-- Fila 2: Filtros --}}
            <div class="flex flex-col lg:flex-row gap-4">
                
                {{-- Grupo de Filtros Rápidos de Tiempo --}}
                <div class="inline-flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    @php $ranges = ['today' => 'Hoy', '7days' => '7D', 'this_month' => 'Este Mes', '30days' => '30D', 'this_year' => 'Este Año']; @endphp
                    <div class="flex gap-1 min-w-max">
                        @foreach($ranges as $key => $label)
                            <a href="{{ route('reports.ncf', ['range' => $key, 'ncf_type' => $filters['ncf_type'], 'status' => $filters['status']]) }}" 
                            class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $filters['current_range'] == $key ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Filtro por Tipo de NCF --}}
                <form action="{{ route('reports.ncf') }}" method="GET" class="flex gap-2">
                    <input type="hidden" name="range" value="{{ $filters['current_range'] }}">
                    <input type="hidden" name="start_date" value="{{ $filters['start'] }}">
                    <input type="hidden" name="end_date" value="{{ $filters['end'] }}">
                    
                    <select name="ncf_type" 
                            class="text-xs border-gray-200 rounded-lg p-2 text-gray-600 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500/20"
                            onchange="this.form.submit()">
                        <option value="">Todos los tipos NCF</option>
                        @foreach($ncfTypes as $type)
                            <option value="{{ $type->id }}" {{ $filters['ncf_type'] == $type->id ? 'selected' : '' }}>
                                {{ $type->display_name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" 
                            class="text-xs border-gray-200 rounded-lg p-2 text-gray-600 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500/20"
                            onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        @foreach($availableStatuses as $key => $label)
                            <option value="{{ $key }}" {{ $filters['status'] == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                {{-- Selector de Rango Manual --}}
                <form action="{{ route('reports.ncf') }}" method="GET" 
                    class="flex items-center bg-white border border-gray-200 rounded-xl p-1 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all min-w-max">
                    <input type="hidden" name="range" value="custom">
                    <input type="hidden" name="ncf_type" value="{{ $filters['ncf_type'] }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
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

        {{-- ALERTAS CRÍTICAS DE NCF --}}
        @if(count($criticalAlerts) > 0)
        <div class="mb-8 space-y-4 w-full"> {{-- Cambiado de grid a space-y-4 para ocupar todo el ancho --}}
            @foreach($criticalAlerts->take(4) as $alert)
                <div class="bg-{{ $alert['color'] }}-50 border border-{{ $alert['color'] }}-200 rounded-xl p-4 flex items-start gap-4 w-full shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex-shrink-0 mt-0.5">
                        @if($alert['type'] === 'warning')
                            <x-heroicon-s-exclamation-triangle class="w-6 h-6 text-{{ $alert['color'] }}-600"/>
                        @else
                            <x-heroicon-s-exclamation-circle class="w-6 h-6 text-{{ $alert['color'] }}-600"/>
                        @endif
                    </div>
                    <div class="flex-1"> {{-- flex-1 expande el contenido --}}
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-1">
                            <h4 class="text-sm font-bold text-{{ $alert['color'] }}-900">
                                {{ $alert['title'] }}
                            </h4>
                            {{-- Opcional: Podrías añadir un badge de "Urgente" aquí si es tipo danger --}}
                        </div>
                        <p class="text-sm text-{{ $alert['color'] }}-700 mt-1 leading-relaxed italic">
                            {{ $alert['message'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- KPIs Principales --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <x-dashboard.kpi-card 
                title="NCF Disponibles" 
                :value="number_format($stats['total_available'])" 
                icon="document-text" 
                color="indigo" 
                secondary-text="Correlativos totales activos"
            />

            <x-dashboard.kpi-card 
                title="Secuencias en Alerta" 
                :value="$stats['sequences_in_alert']" 
                icon="exclamation-triangle" 
                color="{{ $stats['sequences_in_alert'] > 0 ? 'orange' : 'green' }}" 
                secondary-text="{{ $stats['sequences_in_alert'] > 0 ? 'Requieren atención' : 'Todo en orden' }}"
            />

            <x-dashboard.kpi-card 
                title="Próximo Vencimiento" 
                :value="$stats['next_expiry_date'] ? $stats['next_expiry_date']->format('d M Y') : 'N/A'" 
                icon="calendar" 
                color="{{ $stats['days_until_expiry'] && $stats['days_until_expiry'] <= 15 ? 'red' : 'blue' }}" 
                {{-- Agregamos floor o number_format para asegurar --}}
                :trend="$stats['days_until_expiry'] !== null ? 'En ' . number_format($stats['days_until_expiry']) . ' días' : ''"
            />

            <x-dashboard.kpi-card 
                title="Consumo Diario" 
                :value="number_format($stats['daily_average'], 1)" 
                icon="chart-bar" 
                color="green" 
                :trend="$stats['days_remaining'] ? 'Duración: ~' . number_format($stats['days_remaining']) . ' días' : 'Sin datos'"
            />

            <x-dashboard.kpi-card 
                title="NCF Emitidos" 
                :value="number_format($stats['total_emitted'])" 
                icon="check-circle" 
                color="green" 
                secondary-text="En el periodo seleccionado"
            />

            <x-dashboard.kpi-card 
                title="NCF Anulados" 
                :value="number_format($stats['total_voided'])" 
                icon="x-circle" 
                color="red" 
                :trend="$stats['void_rate'] . '% Tasa de anulación'"
            />

            <x-dashboard.kpi-card 
                title="Días Restantes" 
                :value="$stats['days_remaining'] ? number_format($stats['days_remaining']) : 'N/A'" 
                icon="clock" 
                color="{{ $stats['days_remaining'] && $stats['days_remaining'] <= 30 ? 'orange' : 'blue' }}" 
                secondary-text="Según consumo actual"
            />

            <x-dashboard.kpi-card 
                title="Tasa de Anulación" 
                :value="$stats['void_rate'] . '%'" 
                icon="exclamation-circle" 
                color="{{ $stats['void_rate'] > 5 ? 'orange' : 'green' }}" 
                secondary-text="{{ $stats['void_rate'] > 5 ? 'Revisar procesos' : 'Nivel aceptable' }}"
            />
        </div>

        {{-- Gráficos Principales --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            {{-- Línea de Tiempo de Emisión --}}
            <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Emisión de NCF por Día</h3>
                <div id="chart-timeline" class="min-h-[350px] w-full"></div>
            </div>

            {{-- Uso por Tipo de NCF --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-6 uppercase tracking-wider">Uso por Tipo</h3>
                <div id="chart-usage-type" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- Progreso de Secuencias y Motivos de Anulación --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            {{-- Progreso de Secuencias Activas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Progreso de Secuencias</h3>
                <div class="space-y-4">
                    @foreach($charts['sequence_progress']->take(5) as $seq)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-medium text-gray-700">{{ $seq['label'] }}</span>
                                <span class="text-xs font-bold text-gray-900">{{ $seq['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all {{ $seq['percentage'] > 80 ? 'bg-red-500' : ($seq['percentage'] > 50 ? 'bg-orange-500' : 'bg-green-500') }}" 
                                     style="width: {{ $seq['percentage'] }}%"></div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-[10px] text-gray-500">{{ number_format($seq['used']) }} / {{ number_format($seq['total']) }}</span>
                                <span class="text-[10px] font-semibold text-gray-600">{{ number_format($seq['remaining']) }} disponibles</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Motivos de Anulación --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-6 uppercase tracking-wider">Motivos de Anulación</h3>
                <div id="chart-cancellations" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- Tabla: Estado de Secuencias Activas --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Estado de Secuencias</h3>
                <span class="px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">
                    {{ count($activeSequences) }} secuencias
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold tracking-widest">
                            <th class="px-6 py-3">Tipo NCF</th>
                            <th class="px-6 py-3">Serie</th>
                            <th class="px-6 py-3">Rango</th>
                            <th class="px-6 py-3">Actual</th>
                            <th class="px-6 py-3 text-right">Disponibles</th>
                            <th class="px-6 py-3 text-right">Progreso</th>
                            <th class="px-6 py-3 text-right">Vencimiento</th>
                            <th class="px-6 py-3 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($activeSequences as $seq)
                        <tr class="hover:bg-gray-50 transition-colors {{ $seq->is_low ? 'bg-orange-50/30' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-900">{{ $seq->type_name }}</div>
                                <div class="text-[11px] text-gray-500">{{ $seq->type_code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">
                                    {{ $seq->series }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                {{ number_format($seq->from) }} - {{ number_format($seq->to) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-gray-900">{{ number_format($seq->current) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.badge :variant="$seq->remaining < 50 ? 'error' : 'success'" size="sm" :dot="false">
                                    {{ number_format($seq->remaining) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $seq->progress > 80 ? 'bg-red-500' : ($seq->progress > 50 ? 'bg-orange-500' : 'bg-green-500') }}" 
                                             style="width: {{ $seq->progress }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-700">{{ $seq->progress }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="text-xs text-gray-700 font-medium">{{ $seq->expiry_date->format('d M Y') }}</div>
                                <div class="text-[10px] {{ $seq->days_to_expiry <= 15 ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                    {{ $seq->days_to_expiry > 0 ? $seq->days_to_expiry . ' días' : 'Vencida' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.badge :variant="match($seq->calculated_status) {
                                        \App\Models\Sales\Ncf\NcfSequence::STATUS_ACTIVE => 'success',
                                        \App\Models\Sales\Ncf\NcfSequence::STATUS_EXHAUSTED => 'warning',
                                        \App\Models\Sales\Ncf\NcfSequence::STATUS_EXPIRED => 'error',
                                        default => 'slate',
                                    }" size="sm" :dot="false">
                                    {{ $seq->status_label }}
                                </x-ui.badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-3 text-gray-400"/>
                                <p class="text-sm font-medium">No hay secuencias registradas</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabla: Logs Recientes de Uso --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Últimas Emisiones de NCF</h3>
                <a href="{{ route('finance.ncf.logs.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                    Ver todos →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase text-[11px] font-bold tracking-widest">
                            <th class="px-6 py-3">NCF Completo</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Cliente / Factura</th>
                            <th class="px-6 py-3">Usuario</th>
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentLogs as $log)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded">
                                    {{ $log->full_ncf }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs font-medium text-gray-900">{{ $log->type->name }}</div>
                                <div class="text-[10px] text-gray-500">{{ $log->type->code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($log->sale)
                                    <div class="text-xs font-medium text-gray-900">{{ $log->sale->client->name ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-gray-500">Factura: {{ $log->sale->invoice_number }}</div>
                                @else
                                    <span class="text-xs text-gray-400">Sin venta asociada</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                {{ $log->user->name ?? 'Sistema' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs text-gray-700">{{ $log->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-gray-500">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.badge :variant="match($log->status) {
                                        \App\Models\Sales\Ncf\NcfLog::STATUS_USED => 'info',
                                        \App\Models\Sales\Ncf\NcfLog::STATUS_VOIDED => 'slate',
                                        default => 'slate',
                                    }" size="sm" :dot="false">
                                    {{ \App\Models\Sales\Ncf\NcfLog::getStatuses()[$log->status] ?? $log->status }}
                                </x-ui.badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <p class="text-sm font-medium">No hay registros en el periodo seleccionado</p>
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
            let timelineChart, usageTypeChart, cancellationsChart;

            setTimeout(() => {
                const chartsData = {
                    timeline: {!! json_encode($charts['timeline']->pluck('date')) !!},
                    timelineValues: {!! json_encode($charts['timeline']->pluck('total')) !!},
                    usageLabels: {!! json_encode($charts['usage_by_type']['labels']) !!},
                    usageValues: {!! json_encode($charts['usage_by_type']['values']) !!},
                    cancellationLabels: {!! json_encode($charts['cancellation_reasons']['labels']) !!},
                    cancellationValues: {!! json_encode($charts['cancellation_reasons']['values']) !!}
                };

                // 1. Gráfico de Timeline
                const timelineEl = document.querySelector("#chart-timeline");
                if (timelineEl) {
                    timelineChart = new ApexCharts(timelineEl, {
                        series: [{ name: 'NCF Emitidos', data: chartsData.timelineValues.map(Number) }],
                        chart: { type: 'area', height: 350, toolbar: { show: false }, zoom: { enabled: false } },
                        colors: ['#6366F1'],
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: chartsData.timeline },
                        yaxis: { labels: { formatter: (val) => Math.round(val) } }
                    });
                    timelineChart.render();
                }

                // 2. Gráfico de Uso por Tipo
                const usageEl = document.querySelector("#chart-usage-type");
                if (usageEl && chartsData.usageValues.length > 0) {
                    usageTypeChart = new ApexCharts(usageEl, {
                        series: chartsData.usageValues.map(Number),
                        chart: { type: 'donut', height: 320 },
                        labels: chartsData.usageLabels,
                        colors: ['#10B981', '#6366F1', '#F59E0B', '#EF4444', '#8B5CF6'],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '70%' } } }
                    });
                    usageTypeChart.render();
                }

                // 3. Gráfico de Motivos de Anulación
                const cancellationsEl = document.querySelector("#chart-cancellations");
                if (cancellationsEl && chartsData.cancellationValues.length > 0) {
                    cancellationsChart = new ApexCharts(cancellationsEl, {
                        series: chartsData.cancellationValues.map(Number),
                        chart: { type: 'pie', height: 320 },
                        labels: chartsData.cancellationLabels,
                        colors: ['#EF4444', '#F59E0B', '#EC4899', '#8B5CF6', '#14B8A6'],
                        legend: { position: 'bottom' },
                        dataLabels: { 
                            enabled: true,
                            formatter: function(val, opts) {
                                return opts.w.config.series[opts.seriesIndex];
                            }
                        }
                    });
                    cancellationsChart.render();
                }
            }, 150);

            // Manejo de resize
            window.addEventListener('resize-charts', () => {
                if(timelineChart) timelineChart.windowResizeHandler();
                if(usageTypeChart) usageTypeChart.windowResizeHandler();
                if(cancellationsChart) cancellationsChart.windowResizeHandler();
            });
        });
    </script>
    @endpush
</x-app-layout>