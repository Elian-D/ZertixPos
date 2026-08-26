<x-app-layout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        
        {{-- Header & Filtros --}}
        <div class="mb-8 space-y-6">
            
            {{-- Fila 1: Título y Botón --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-shrink-0">
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Centro de Inventario</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestión de existencias y flujo logístico</p>
                </div>
                
                {{-- Botón Registrar Entrada --}}
                <x-ui.button x-on:click="$dispatch('open-modal', 'register-input')" variant="primary" :hoverEffect="true" iconLeft="heroicon-s-plus-circle" class="whitespace-nowrap">
                    Registrar Entrada
                </x-ui.button>
            </div>

            {{-- Fila 2: Filtros --}}
            <div class="flex flex-col lg:flex-row gap-4">
                
                {{-- Filtros Rápidos --}}
                <div class="inline-flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                    @php $ranges = ['today' => 'Hoy', '7days' => '7D', 'this_month' => 'Este Mes', '30days' => '30D']; @endphp
                    <div class="flex gap-1 min-w-max">
                        @foreach($ranges as $key => $label)
                            <a href="{{ route('reports.inventory', ['range' => $key]) }}" 
                               class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $filters['current_range'] == $key ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Selector de Rango Manual --}}
                <form action="{{ route('reports.inventory') }}" method="GET" 
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


        {{-- KPIs divididos en 2 filas de 3 --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <x-dashboard.kpi-card 
                title="Productos Totales" 
                :value="$stats['total_products']" 
                icon="cube" 
                color="blue" 
                secondary-text="Items en catálogo"
            />

            <x-dashboard.kpi-card 
                title="Existencias" 
                :value="number_format($stats['total_stock'], 0)" 
                icon="archive-box" 
                color="green" 
                secondary-text="Unidades disponibles"
                href="{{ route('inventory.stocks.index') }}"
            />

            <x-dashboard.kpi-card 
                title="Stock Bajo" 
                :value="$stats['low_stock']" 
                icon="exclamation-triangle" 
                color="red" 
                :trend="$stats['low_stock'] > 0 ? 'Requiere atención' : 'Todo OK'"
                :trend-up="false"
            />

            <x-dashboard.kpi-card 
                title="Almacenes Activos" 
                :value="$stats['active_warehouses']" 
                icon="building-office-2" 
                color="indigo" 
                secondary-text="Ubicaciones operativas"
                href="{{ route('inventory.warehouses.index') }}"
            />

            <x-dashboard.kpi-card 
                title="Entradas (Periodo)" 
                :value="number_format($stats['total_inputs'], 0)" 
                icon="arrow-up-circle" 
                color="green" 
                secondary-text="Nuevas unidades en el rango seleccionado"
            />

            <x-dashboard.kpi-card 
                title="Salidas (Periodo)" 
                :value="number_format($stats['total_outputs'], 0)" 
                icon="arrow-down-circle" 
                color="red" 
                secondary-text="Unidades despachadas en el rango seleccionado"
            />

        </div>

        {{-- Layout principal: Gráficos (Izquierda) + Sidebar (Derecha) --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            
            {{-- Columna principal: Gráficos (2/3 del ancho) --}}
            <div class="xl:col-span-2 space-y-6">
                
                {{-- 1. Flujo de Inventario (full width) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Flujo de Inventario</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Entradas vs Salidas</p>
                        </div>
                        <x-ui.badge variant="info" size="sm" :dot="false">
                            {{ ucfirst($filters['current_range']) }}
                        </x-ui.badge>
                    </div>
                    <div id="chart-movements" class="min-h-[350px] w-full"></div>
                </div>

                {{-- 2. Stock por Almacén (full width) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6">Stock por Almacén</h3>
                    <div id="chart-warehouses" class="min-h-[350px] w-full"></div>
                </div>

                {{-- 3. Mayor Rotación (full width) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6">Mayor Rotación</h3>
                    <div id="chart-top-products" class="min-h-[350px] w-full"></div>
                </div>
            </div>

            {{-- Sidebar: Actividad reciente (1/3 del ancho) --}}
            <div class="space-y-6">
                
                {{-- Últimos Movimientos --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-gray-900">Actividad Reciente</h3>
                        <a href="{{ route('inventory.movements.index') }}" 
                           class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold hover:underline">
                            Ver kardex
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($recentMovements as $mv)
                            <div class="flex items-start gap-3 pb-3 border-b border-gray-50 last:border-0 last:pb-0">
                                <div class="flex-shrink-0 p-2 rounded-lg {{ $mv->type === 'input' ? 'bg-green-50' : 'bg-red-50' }}">
                                    @if($mv->type === 'input')
                                        <x-heroicon-s-arrow-up-circle class="w-5 h-5 text-green-600"/>
                                    @else
                                        <x-heroicon-s-arrow-down-circle class="w-5 h-5 text-red-600"/>
                                    @endif
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">
                                        {{ $mv->product->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $mv->warehouse->name }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 uppercase mt-1">
                                        {{ $mv->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                
                                <div class="flex-shrink-0 text-right">
                                    <span class="font-mono font-bold text-sm {{ $mv->type === 'input' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $mv->type === 'input' ? '+' : '-' }}{{ number_format(abs($mv->quantity), 0) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <x-heroicon-o-inbox class="w-12 h-12 text-gray-300 mx-auto mb-2"/>
                                <p class="text-sm text-gray-500">No hay movimientos recientes</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Lista de Stock Bajo/Crítico --}}
                @if($stats['low_stock'] > 0)
                    <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl border border-red-100 p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-600"/>
                            <h3 class="text-sm font-bold text-red-900">Alertas de Stock</h3>
                        </div>
                        <p class="text-sm text-red-700 mb-4">
                            {{ $stats['low_stock'] }} {{ Str::plural('producto', $stats['low_stock']) }} 
                            {{ $stats['low_stock'] === 1 ? 'requiere' : 'requieren' }} reabastecimiento
                        </p>
                        
                        <div class="space-y-3 mb-4">
                            @foreach($lowStockProducts as $stock)
                                <div class="flex items-center justify-between bg-white/60 rounded-lg p-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs font-semibold text-gray-800 truncate">
                                                {{ $stock->product->name }}
                                            </p>
                                            @if($stock->quantity <= 0)
                                                <x-ui.badge variant="error" size="sm" :dot="false" class="flex-shrink-0">
                                                    AGOTADO
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-500">
                                            {{ $stock->warehouse->name }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end ml-2">
                                        <span class="text-sm font-bold {{ $stock->quantity <= 0 ? 'text-red-600' : 'text-orange-600' }}">
                                            {{ number_format($stock->quantity, 0) }}
                                        </span>
                                        <p class="text-[9px] text-gray-400 font-medium italic">Min: {{ number_format($stock->min_stock, 0) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <x-ui.button x-on:click="$dispatch('open-modal', 'register-input')" variant="error" iconLeft="heroicon-s-plus-circle" size="sm" :fullWidth="true">
                            Reponer inventario ahora
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sección completa: Productos Bajo en Stock (si necesitas más espacio) --}}
        @if($stats['low_stock'] > 5)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-red-50/50">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-600"/>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Productos Críticos en Stock</h3>
                    </div>
                    <x-ui.badge variant="error" size="sm" :dot="false">
                        {{ $stats['low_stock'] }} productos
                    </x-ui.badge>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($lowStockProducts as $stock)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-red-300 transition">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $stock->product->name }}
                                        </p>
                                        @if($stock->quantity <= 0)
                                            <x-ui.badge variant="error" size="sm" :dot="false" class="flex-shrink-0">
                                                AGOTADO
                                            </x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $stock->warehouse->name }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end ml-3">
                                    <span class="text-lg font-bold {{ $stock->quantity <= 0 ? 'text-red-600' : 'text-orange-600' }}">
                                        {{ number_format($stock->quantity, 0) }}
                                    </span>
                                    <p class="text-[10px] text-gray-400 font-medium italic">Min: {{ number_format($stock->min_stock, 0) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let movementsChart, warehousesChart, productsChart;

            setTimeout(() => {
                const data = {
                    history: {
                        labels: {!! json_encode($charts['history']['labels']) !!},
                        inputs: {!! json_encode($charts['history']['inputs']) !!},
                        outputs: {!! json_encode($charts['history']['outputs']) !!}
                    },
                    distribution: {
                        labels: {!! json_encode($charts['distribution']['labels']) !!},
                        values: {!! json_encode($charts['distribution']['values']) !!}
                    },
                    top_products: {
                        labels: {!! json_encode($charts['top_products']['labels']) !!},
                        values: {!! json_encode($charts['top_products']['values']) !!}
                    }
                };

                // 1. Gráfico de Flujo (Área)
                const movementsEl = document.querySelector("#chart-movements");
                if (movementsEl) {
                    movementsChart = new ApexCharts(movementsEl, {
                        series: [
                            { name: 'Entradas', data: data.history.inputs.map(Number) },
                            { name: 'Salidas', data: data.history.outputs.map(Number) }
                        ],
                        chart: { type: 'area', height: 350, toolbar: { show: false }, zoom: { enabled: false } },
                        colors: ['#10B981', '#EF4444'],
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: data.history.labels },
                        yaxis: { labels: { formatter: (val) => val.toLocaleString() } },
                        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
                        tooltip: { theme: 'light' }
                    });
                    movementsChart.render();
                }

                // 2. Gráfico de Distribución (Donut) - Full width
                const warehousesEl = document.querySelector("#chart-warehouses");
                if (warehousesEl) {
                    warehousesChart = new ApexCharts(warehousesEl, {
                        series: data.distribution.values.map(Number),
                        chart: { type: 'donut', height: 350 },
                        labels: data.distribution.labels,
                        colors: ['#6366F1', '#10B981', '#F59E0B', '#3B82F6', '#EC4899'],
                        legend: { position: 'bottom', fontSize: '11px', markers: { width: 8, height: 8 } },
                        plotOptions: { 
                            pie: { 
                                donut: { 
                                    size: '70%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            fontSize: '14px',
                                            fontWeight: 600,
                                            color: '#1f2937'
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' }
                    });
                    warehousesChart.render();
                }

                // 3. Top Productos (Barras) - Full width
                const productsEl = document.querySelector("#chart-top-products");
                if (productsEl) {
                    productsChart = new ApexCharts(productsEl, {
                        series: [{ name: 'Movimientos', data: data.top_products.values.map(Number) }],
                        chart: { type: 'bar', height: 350, toolbar: { show: false } },
                        plotOptions: { bar: { borderRadius: 6, horizontal: true, distributed: true, barHeight: '60%' } },
                        colors: ['#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981'],
                        xaxis: { categories: data.top_products.labels },
                        yaxis: { labels: { maxWidth: 200 } },
                        dataLabels: { 
                            enabled: true, 
                            textAnchor: 'start',
                            offsetX: 10,
                            style: { fontSize: '11px', fontWeight: 600, colors: ['#fff'] } 
                        },
                        legend: { show: false }
                    });
                    productsChart.render();
                }
            }, 150);

            window.addEventListener('resize-charts', () => {
                if(movementsChart) movementsChart.windowResizeHandler();
                if(warehousesChart) warehousesChart.windowResizeHandler();
                if(productsChart) productsChart.windowResizeHandler();
            });
        });
    </script>
    @endpush

    {{-- Modal de Entrada de Inventario --}}
    <x-modal name="register-input" maxWidth="md">
        <x-form-header 
            title="Registrar Entrada de Inventario" 
            subtitle="Incrementar stock por compra, producción o devolución" />

        <form action="{{ route('inventory.movements.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="type" value="input">

            <div class="space-y-4">
                <x-ui.forms.select
                    label="Producto"
                    name="product_id"
                    id="input_product_id"
                    placeholder="Seleccione el producto..."
                    :error="$errors->first('product_id')"
                    required
                >
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.select
                    label="Almacén de Destino"
                    name="warehouse_id"
                    id="input_warehouse_id"
                    placeholder="Seleccione almacén..."
                    :error="$errors->first('warehouse_id')"
                    required
                >
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.input
                    label="Cantidad"
                    id="input_quantity"
                    name="quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    hint="Se sumará al stock actual del almacén"
                    :error="$errors->first('quantity')"
                    required
                />

                <x-ui.forms.textarea
                    label="Notas"
                    name="description"
                    id="input_description"
                    :rows="2"
                    placeholder="Ej: Compra factura #1234, Producción lote #105"
                    :error="$errors->first('description')"
                    required
                ></x-ui.forms.textarea>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Registrar Entrada</x-ui.button>
            </div>
        </form>
    </x-modal>
</x-app-layout>