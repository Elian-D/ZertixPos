<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Header Principal --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-gray-100 pb-6">
                <div>
                    <nav class="flex mb-2" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2 text-[10px] uppercase tracking-wider font-bold">
                            <li class="inline-flex items-center text-gray-400">
                                <a href="{{ route('sales.pos.sessions.index') }}" class="hover:text-indigo-600 transition">Turnos POS</a>
                            </li>
                            <x-heroicon-s-chevron-right class="w-3 h-3 text-gray-300" />
                            <li class="text-gray-500">Turno #{{ $posSession->id }}</li>
                        </ol>
                    </nav>
                    <h2 class="font-black text-3xl text-gray-800 tracking-tight">
                        Detalle de Turno: <span class="text-indigo-600">{{ $posSession->terminal->name ?? 'Terminal eliminada' }}</span>
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('sales.pos.sessions.print', $posSession) }}" target="_blank"
                       class="bg-white text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-gray-50 transition shadow-sm active:scale-95">
                        <x-heroicon-s-printer class="w-4 h-4 text-gray-500" />
                        Imprimir Reporte
                    </a>
                    <a href="{{ route('sales.pos.sessions.print', ['pos_session' => $posSession, 'format' => 'ticket']) }}" target="_blank"
                       class="bg-white text-gray-500 border border-gray-200 px-3 py-2.5 rounded-xl text-xs font-bold hover:bg-gray-50 transition shadow-sm active:scale-95"
                       title="Versión ticket 80mm">
                        Ticket
                    </a>
                    {{-- Botón "Movimiento Manual": oculto, ver Fase 9.1 en docs/features/POS-Interfaz.md --}}
                    {{-- @if($posSession->status === 'open')
                        <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'register-cash-movement')">
                            <x-heroicon-s-plus class="w-4 h-4 mr-2" />
                            Movimiento Manual
                        </x-primary-button>
                    @endif --}}

                    {{-- Mismo botón/estilo "Cerrar Turno" que ya existe en el navbar del Workspace --}}
                    @if($posSession->isOpen() && auth()->user()->can('pos sessions manage'))
                        <a href="{{ route('sales.pos.sessions.close-form', $posSession) }}"
                           title="Cerrar Turno"
                           class="flex items-center gap-1.5 text-sm font-bold text-white bg-gray-800 hover:bg-gray-900 px-4 py-2.5 rounded-xl transition-colors shadow-sm active:scale-95">
                            <x-heroicon-s-lock-closed class="w-4 h-4" />
                            <span>Cerrar Turno</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Cards de Auditoría (Abrió, Cerró, Periodo, Estado) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition duration-300">
                    <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                        <x-heroicon-s-arrow-up-circle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Abrió</p>
                        <p class="text-sm font-bold text-gray-800">{{ $posSession->openedBy->name ?? $posSession->user->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition duration-300">
                    <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                        <x-heroicon-s-arrow-down-circle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Cerró</p>
                        <p class="text-sm font-bold text-gray-800">{{ $posSession->closedBy->name ?? ($posSession->isOpen() ? 'Turno en curso' : 'N/A') }}</p>
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
                        <p class="text-sm font-black uppercase">{{ \App\Models\Sales\Pos\PosSession::getStatuses()[$posSession->status] ?? $posSession->status }}</p>
                    </div>
                </div>
            </div>

            {{-- Notas del turno: observación general del cierre + motivo de descuadre
                 (Fase 9.4, `difference_reason`/`difference_notes`) — dos cosas distintas
                 que pueden aparecer independientemente una de la otra (puede haber
                 descuadre sin notas generales, o notas generales sin descuadre). Va antes
                 del arqueo a propósito — es contexto que hay que leer antes de interpretar
                 la diferencia, no un detalle secundario para el final de la página. --}}
            @if($posSession->notes || $posSession->difference_reason)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-3">
                    <x-heroicon-s-chat-bubble-left-ellipsis class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                    <div class="space-y-2">
                        @if($posSession->difference_reason)
                            <div>
                                <p class="text-[10px] uppercase font-black text-amber-600 tracking-widest mb-1">Motivo del Descuadre</p>
                                <p class="text-sm text-amber-800 font-semibold">{{ \App\Models\Sales\Pos\PosSession::getReasons()[$posSession->difference_reason] ?? $posSession->difference_reason }}</p>
                                @if($posSession->difference_notes)
                                    <p class="text-sm text-amber-800 mt-1">{{ $posSession->difference_notes }}</p>
                                @endif
                            </div>
                        @endif
                        @if($posSession->notes)
                            <div>
                                <p class="text-[10px] uppercase font-black text-amber-600 tracking-widest mb-1">Notas del Turno</p>
                                <p class="text-sm text-amber-800">{{ $posSession->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

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

                            @php
                                // Si está cerrada, usamos la verdad grabada. Si está abierta, calculamos.
                                $displayExpected = $posSession->isOpen()
                                    ? $posSession->calculateExpected()
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

            {{-- Detalle de Ventas --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-black text-gray-800 mb-4 flex items-center gap-2">
                    <x-heroicon-s-shopping-cart class="w-4 h-4 text-indigo-500" />
                    Detalle de Ventas
                </h3>

                @if(count($salesDetail))
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-[10px] uppercase text-gray-400 border-b border-gray-100">
                                    <th class="py-2 pr-4">Hora</th>
                                    <th class="py-2 pr-4">#</th>
                                    <th class="py-2 pr-4">Cliente</th>
                                    <th class="py-2 pr-4">Cajero</th>
                                    <th class="py-2 pr-4 text-right">Cant.</th>
                                    <th class="py-2 pr-4">Método de Pago</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesDetail as $row)
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 pr-4 text-gray-500">{{ $row['hora'] }}</td>
                                        <td class="py-2 pr-4 font-mono text-gray-500">{{ $row['numero'] }}</td>
                                        <td class="py-2 pr-4 font-medium text-gray-700">{{ $row['cliente'] }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $row['cajero'] }}</td>
                                        <td class="py-2 pr-4 text-right text-gray-600">{{ rtrim(rtrim(number_format($row['cantidad'], 2), '0'), '.') }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $row['metodo'] }}</td>
                                        <td class="py-2 text-right font-mono font-bold text-gray-800">${{ number_format($row['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-gray-400 italic">Sin ventas registradas en este turno todavía.</p>
                @endif
            </div>

            {{-- Resumen de ventas por forma de pago --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-black text-gray-800 mb-4 flex items-center gap-2">
                    <x-heroicon-s-banknotes class="w-4 h-4 text-indigo-500" />
                    Resumen por Forma de Pago
                </h3>

                @if(count($columns))
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-[10px] uppercase text-gray-400 border-b border-gray-100">
                                    <th class="py-2 pr-4">Concepto</th>
                                    @foreach($columns as $col)
                                        <th class="py-2 pr-4 text-right">{{ $col }}</th>
                                    @endforeach
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($breakdownRows as $row)
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 pr-4 font-medium text-gray-700">{{ $row['concepto'] }}</td>
                                        @foreach($columns as $col)
                                            <td class="py-2 pr-4 text-right font-mono text-gray-600">${{ number_format($row['methods'][$col] ?? 0, 2) }}</td>
                                        @endforeach
                                        <td class="py-2 text-right font-mono font-bold text-gray-800">${{ number_format($row['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-gray-400 italic">Sin ventas en efectivo/tarjeta/transferencia registradas en este turno todavía.</p>
                @endif

                @if($creditTotal > 0)
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-4">
                        Ventas totales del turno: <span class="font-bold">${{ number_format($totalSalesWithCredit, 2) }}</span>
                        — de las cuales <span class="font-bold">${{ number_format($creditTotal, 2) }}</span> fueron a
                        <span class="font-bold">Crédito (CxC)</span>, no exigible en caja (no forma parte del arqueo).
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal de Registro: oculto, ver Fase 9.1 en docs/features/POS-Interfaz.md --}}
    {{-- @include('sales.pos.cash-movements.partials.modal-movement', ['sessionId' => $posSession->id]) --}}
</x-app-layout>