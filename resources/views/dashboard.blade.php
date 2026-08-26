<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Panel de Control Principal') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <livewire:onboarding.onboarding-checklist />

            <section>
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Métricas Principales</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    
                    <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-zertix-primary-500 hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Ventas Netas (Mes)</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ config('regional.currency_symbol') }}15,450.00</p>
                            </div>
                            <x-heroicon-s-currency-dollar class="w-8 h-8 text-zertix-primary-500 opacity-70" />
                        </div>
                        <p class="text-xs text-green-500 mt-2 flex items-center">
                            <x-heroicon-s-arrow-up class="w-4 h-4 mr-1" />
                            +8.5% desde el mes pasado
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-green-500 hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Clientes Activos</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">450</p>
                            </div>
                            <x-heroicon-s-user-group class="w-8 h-8 text-green-500 opacity-70" />
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Total registrado: 1,200
                        </p>
                    </div>
                    
                    <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-yellow-500 hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Rutas Asignadas (Hoy)</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">12 / 15</p>
                            </div>
                            <x-heroicon-s-map class="w-8 h-8 text-yellow-500 opacity-70" />
                        </div>
                        <p class="text-xs text-red-500 mt-2 flex items-center">
                             <x-heroicon-s-clock class="w-4 h-4 mr-1" />
                            3 Rutas pendientes
                        </p>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-pink-500 hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tasa de Conversión</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">28.5%</p>
                            </div>
                            <x-heroicon-s-chart-bar class="w-8 h-8 text-pink-500 opacity-70" />
                        </div>
                        <p class="text-xs text-blue-500 mt-2 flex items-center">
                             <x-heroicon-s-sparkles class="w-4 h-4 mr-1" />
                            Meta: 35%
                        </p>
                    </div>
                    
                </div>
            </section>

            ---
            
            <section>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Tendencia de Ingresos (Últimos 6 meses)</h3>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded border border-dashed border-gray-300">
                             

[Image of a line chart showing increasing revenue over 6 months]

                            <p class="text-gray-500">Aquí iría el Gráfico de Líneas (ej. Chart.js o ApexCharts)</p>
                        </div>
                    </div>
                    
                    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Ventas por Categoría</h3>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded border border-dashed border-gray-300">
                             
                            <p class="text-gray-500">Aquí iría el Gráfico de Pastel (ej. Chart.js o ApexCharts)</p>
                        </div>
                    </div>
                    
                </div>
            </section>
            
            ---

            <section>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Últimas Ventas Registradas</h3>
                        <ul class="divide-y divide-gray-200">
                            <li class="py-3 flex justify-between items-center text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">Venta #1023</p>
                                    <p class="text-gray-500">Cliente: Juan Pérez</p>
                                </div>
                                <span class="font-bold text-green-600">{{ config('regional.currency_symbol') }}450.00</span>
                            </li>
                            <li class="py-3 flex justify-between items-center text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">Venta #1022</p>
                                    <p class="text-gray-500">Cliente: María Gómez</p>
                                </div>
                                <span class="font-bold text-green-600">{{ config('regional.currency_symbol') }}120.50</span>
                            </li>
                             <li class="py-3 flex justify-between items-center text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">Venta #1021</p>
                                    <p class="text-gray-500">Cliente: Suministros ABC</p>
                                </div>
                                <span class="font-bold text-green-600">{{ config('regional.currency_symbol') }}1,200.00</span>
                            </li>
                            <li class="py-3 text-center">
                                <a href="/ventas" class="text-zertix-primary-600 hover:text-zertix-primary-800 font-medium text-sm">Ver todo el historial</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Tareas y Alertas</h3>
                        <ul class="space-y-3">
                            <li class="p-3 bg-red-50 border-l-4 border-red-500 rounded flex items-center justify-between text-sm">
                                <span class="text-red-700 font-medium">Factura Pendiente de Cobro ($200)</span>
                                <x-heroicon-s-bell class="w-5 h-5 text-red-500" />
                            </li>
                            <li class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded flex items-center justify-between text-sm">
                                <span class="text-blue-700 font-medium">Asignar 3 rutas nuevas hoy</span>
                                <x-heroicon-s-clipboard class="w-5 h-5 text-blue-500" />
                            </li>
                            <li class="p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded flex items-center justify-between text-sm">
                                <span class="text-yellow-700 font-medium">Revisar stock bajo (2 productos)</span>
                                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-yellow-500" />
                            </li>
                        </ul>
                    </div>
                    
                </div>
            </section>

        </div>
    </div>
</x-app-layout>