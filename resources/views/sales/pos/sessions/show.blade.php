<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Header Principal --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-gray-100 pb-6">
                <div>
                    <nav class="flex mb-2" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2 text-[10px] uppercase tracking-wider font-bold">
                            <li class="inline-flex items-center text-gray-400">
                                <a href="{{ route('sales.pos.sessions.index') }}" class="hover:text-indigo-600 transition">Sesiones POS</a>
                            </li>
                            <x-heroicon-s-chevron-right class="w-3 h-3 text-gray-300" />
                            <li class="text-gray-500">Sesión #{{ $posSession->id }}</li>
                        </ol>
                    </nav>
                    <h2 class="font-black text-3xl text-gray-800 tracking-tight">
                        Detalle de Sesión: <span class="text-indigo-600">{{ $posSession->terminal->name ?? 'Terminal eliminada' }}</span>
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <button onclick="window.print()" class="bg-white text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-gray-50 transition shadow-sm active:scale-95">
                        <x-heroicon-s-printer class="w-4 h-4 text-gray-500" />
                        Imprimir Reporte
                    </button>
                    {{-- Botón para registrar movimiento rápido si la sesión está abierta --}}
                    @if($posSession->status === 'open')
                        <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'register-cash-movement')">
                            <x-heroicon-s-plus class="w-4 h-4 mr-2" />
                            Movimiento Manual
                        </x-primary-button>
                    @endif
                </div>
            </div>

            {{-- Cards de Auditoría (Responsable, Periodo, Estado) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition duration-300">
                    <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                        <x-heroicon-s-user class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Responsable</p>
                        <p class="text-sm font-bold text-gray-800">{{ $posSession->user->name }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition duration-300">
                    <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                        <x-heroicon-s-clock class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Periodo</p>
                        <p class="text-xs font-bold text-gray-800">
                            {{ $posSession->opened_at->format('d/m/Y H:i') }} - 
                            <span class="{{ $posSession->closed_at ? '' : 'text-green-600' }}">
                                {{ $posSession->closed_at ? $posSession->closed_at->format('H:i') : 'En curso...' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition duration-300">
                    @php $styles = \App\Models\Sales\Pos\PosSession::getStatusStyles(); @endphp
                    <div class="p-3 {{ $styles[$posSession->status] }} rounded-xl">
                        <x-heroicon-s-shield-check class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Estado Actual</p>
                        <p class="text-sm font-black uppercase italic">{{ $posSession->status }}</p>
                    </div>
                </div>
            </div>

            {{-- Resumen Financiero Dinámico --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100">
                <div class="p-8">
                    <h3 class="text-lg font-black text-gray-800 mb-6 flex items-center gap-2">
                        <x-heroicon-s-calculator class="w-5 h-5 text-indigo-500" />
                        Resumen de Arqueo
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <div class="space-y-3">
                            <div class="flex justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <span class="text-xs text-gray-600 font-medium">(+) Fondo Inicial</span>
                                <span class="text-xs font-mono font-bold">${{ number_format($posSession->opening_balance, 2) }}</span>
                            </div>
                            <div class="flex justify-between p-3 bg-green-50/50 rounded-xl border border-green-100 text-green-700">
                                <span class="text-xs font-medium">(+) Ventas en Efectivo</span>
                                <span class="text-xs font-mono font-bold">${{ number_format($posSession->cash_sales ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between p-3 bg-blue-50/50 rounded-xl border border-blue-100 text-blue-700">
                                <span class="text-xs font-medium">(+) Entradas Manuales</span>
                                <span class="text-xs font-mono font-bold">${{ number_format($cashIn, 2) }}</span>
                            </div>
                            <div class="flex justify-between p-3 bg-red-50/50 rounded-xl border border-red-100 text-red-700">
                                <span class="text-xs font-medium">(-) Salidas / Gastos</span>
                                <span class="text-xs font-mono font-bold">(${{ number_format($cashOut, 2) }})</span>
                            </div>
                            
                            @php 
                                // Si está cerrada, usamos la verdad grabada. Si está abierta, calculamos.
                                $displayExpected = $posSession->isOpen() 
                                    ? ($posSession->opening_balance + ($posSession->cash_sales ?? 0) + $cashIn) - $cashOut
                                    : $posSession->expected_balance;
                            @endphp

                            <div class="flex justify-between p-5 bg-indigo-600 rounded-xl text-white shadow-lg shadow-indigo-100 mt-4">
                                <span class="font-bold">(=) Monto Esperado en Caja</span>
                                <span class="text-lg font-mono font-black">${{ number_format($displayExpected, 2) }}</span>
                            </div>
                        </div>

                        {{-- Resultado del Arqueo --}}
                        <div class="flex flex-col justify-center items-center p-8 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                            @if($posSession->isClosed())
                                <p class="text-[10px] uppercase font-black text-gray-400 mb-2">Monto Real Reportado</p>
                                <h4 class="text-4xl font-black text-gray-900 mb-4">${{ number_format($posSession->closing_balance, 2) }}</h4>
                                
                                {{-- ¡USAMOS LA COLUMNA DIRECTA! --}}
                                <div class="inline-flex items-center px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest {{ $posSession->difference >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $posSession->difference == 0 ? 'Caja Cuadrada' : ($posSession->difference > 0 ? 'Sobrante' : 'Faltante') }} de ${{ number_format(abs($posSession->difference), 2) }}
                                </div>
                            @else
                                <div class="text-center">
                                    <x-heroicon-o-lock-closed class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                                    <p class="text-sm text-gray-500 font-medium italic">El arqueo final estará disponible una vez cerrada la sesión.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Próximamente: Ventas --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-gray-400 italic">
                     <x-heroicon-o-shopping-cart class="w-8 h-8 mb-2 opacity-20" />
                     <p class="text-xs">Próximamente: Desglose por otros métodos de pago.</p>
                </div>

                {{-- Historial de movimientos manuales REAL --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-sm font-black text-gray-800 mb-4 flex items-center gap-2">
                        <x-heroicon-s-arrows-right-left class="w-4 h-4 text-indigo-500" />
                        Movimientos Manuales
                    </h3>
                    
                    @include('sales.pos.cash-movements.partials.table-mini', ['items' => $posSession->cashMovements])
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Registro --}}
    @include('sales.pos.cash-movements.partials.modal-movement', ['sessionId' => $posSession->id])
</x-app-layout>