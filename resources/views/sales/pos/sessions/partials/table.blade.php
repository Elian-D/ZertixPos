<x-data-table :items="$sessions" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($sessions as $session)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- ID Sesión --}}
            @if(in_array('id', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono text-gray-400">
                    #{{ str_pad($session->id, 5, '0', STR_PAD_LEFT) }}
                </td>
            @endif

            {{-- Terminal --}}
            @if(in_array('terminal_id', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="flex items-center font-medium text-gray-900">
                        <x-heroicon-s-computer-desktop class="w-4 h-4 mr-2 text-indigo-500" />
                        {{ $session->terminal->name ?? 'N/A' }}
                    </div>
                </td>
            @endif

            {{-- Abierto Por --}}
            @if(in_array('opened_by_user_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex items-center">
                        <x-heroicon-s-arrow-up-circle class="w-4 h-4 mr-2 text-emerald-400" />
                        {{ $session->openedBy->name ?? 'N/A' }}
                    </div>
                </td>
            @endif

            {{-- Cerrado Por (puede ser distinto de quien abrió) --}}
            @if(in_array('closed_by_user_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex items-center">
                        <x-heroicon-s-arrow-down-circle class="w-4 h-4 mr-2 text-amber-400" />
                        {{ $session->closedBy->name ?? '—' }}
                    </div>
                </td>
            @endif

            {{-- Estado --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    @php
                        $sStyles = \App\Models\Sales\Pos\PosSession::getStatusStyles();
                        $sLabels = \App\Models\Sales\Pos\PosSession::getStatuses();
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm ring-1 ring-inset {{ $sStyles[$session->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $sLabels[$session->status] ?? $session->status }}
                    </span>
                </td>
            @endif

            {{-- Fecha Apertura --}}
            @if(in_array('opened_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <span class="block font-medium text-gray-700">{{ $session->opened_at->format('d/m/Y') }}</span>
                    <span class="text-[10px]">{{ $session->opened_at->format('h:i A') }}</span>
                </td>
            @endif

            {{-- Fecha Cierre --}}
            @if(in_array('closed_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    @if($session->closed_at)
                        <span class="block font-medium text-gray-700">{{ $session->closed_at->format('d/m/Y') }}</span>
                        <span class="text-[10px]">{{ $session->closed_at->format('h:i A') }}</span>
                    @else
                        <span class="text-gray-300 italic">En curso...</span>
                    @endif
                </td>
            @endif

            {{-- Balances --}}
            @if(in_array('opening_balance', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right text-gray-600">
                    <span class="text-[10px] text-gray-400 mr-1">$</span>{{ number_format($session->opening_balance, 2) }}
                </td>
            @endif

            {{-- Balance Esperado --}}
            @if(in_array('expected_balance', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right text-gray-600">
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_CLOSED)
                        <span class="text-[10px] text-gray-400 mr-1">$</span>{{ number_format($session->expected_balance, 2) }}
                    @else
                        <span class="text-gray-300 italic text-[10px]">Calculando...</span>
                    @endif
                </td>
            @endif

            {{-- Balance Final (Arqueo Real) --}}
            @if(in_array('closing_balance', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_CLOSED)
                        <span class="text-[10px] font-normal text-gray-400 mr-1">$</span>{{ number_format($session->closing_balance, 2) }}
                    @else
                        <span class="text-gray-300">---</span>
                    @endif
                </td>
            @endif

            {{-- Diferencia (La Verdad de la BD) --}}
            @if(in_array('difference', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right">
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_CLOSED)
                        {{-- Usamos directamente el campo difference de la migración --}}
                        <span class="{{ $session->difference >= 0 ? ($session->difference == 0 ? 'text-gray-500' : 'text-green-600') : 'text-red-600' }} font-bold">
                            <span class="text-[10px] font-normal mr-1">$</span>{{ number_format($session->difference, 2) }}
                        </span>
                    @else
                        <span class="text-gray-300">---</span>
                    @endif
                </td>
            @endif

            {{-- Notas --}}
            @if(in_array('notes', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400 max-w-[150px] truncate italic" title="{{ $session->notes }}">
                    {{ $session->notes ?? 'Sin observaciones' }}
                </td>
            @endif

            {{-- Fecha Registro --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $session->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    {{-- Ver Reporte de Sesión (X/Z) --}}
                    <a href="{{ route('sales.pos.sessions.show', $session) }}" 
                       class="bg-white border border-gray-200 text-gray-500 hover:bg-indigo-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                       title="Ver Reporte Detallado">
                        <x-heroicon-s-document-chart-bar class="w-4 h-4" />
                    </a>

                    {{-- Si está abierta, ir a la vista dedicada de cierre --}}
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_OPEN && auth()->user()->can('pos sessions manage'))
                        <a href="{{ route('sales.pos.sessions.close-form', $session) }}"
                           class="bg-white border border-gray-200 text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all shadow-sm"
                           title="Realizar Arqueo y Cierre">
                            <x-heroicon-s-lock-closed class="w-4 h-4" />
                        </a>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-calculator class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p class="text-sm">No se encontraron sesiones registradas.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>