<div>

    @if(session('autoPrintSessionUrl'))
        <script>
            // Al cerrar un turno, PosSessionController::close() flashea esta URL y redirige
            // aquí — la abrimos en pestaña nueva automáticamente (mismo patrón que el
            // auto-print de venta en PosWorkspace, Fase 7.4). Dedupe por sessionStorage por
            // si este bloque llegara a procesarse más de una vez para el mismo cierre.
            (function () {
                const url = @js(session('autoPrintSessionUrl'));
                const dedupeKey = 'pos-session-auto-printed:' + url;
                if (sessionStorage.getItem(dedupeKey)) {
                    return;
                }
                sessionStorage.setItem(dedupeKey, '1');
                window.open(url, '_blank');
            })();
        </script>
    @endif

    <x-ui.page-header
        title="Historial de Turnos POS"
        description="Consulta los turnos de caja abiertos y cerrados en las terminales POS."
        :count="$sessions->total()"
        countLabel="turnos"
    >
        <x-slot:actions>
            @can('pos sessions manage')
                <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'open-session-modal')"
                    variant="primary" iconLeft="heroicon-s-lock-open">
                    Nuevo Turno (Apertura)
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$sessions"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select
                        label="Terminal" filterKey="terminal_id"
                        :options="$terminals->pluck('name', 'id')->all()"
                        placeholder="Todas las terminales" />

                    <x-data-table.filter-select
                        label="Abierto Por" filterKey="opened_by_user_id"
                        :options="$users->pluck('name', 'id')->all()"
                        placeholder="Todos los usuarios" />

                    <x-data-table.filter-select
                        label="Cerrado Por" filterKey="closed_by_user_id"
                        :options="$users->pluck('name', 'id')->all()"
                        placeholder="Todos los usuarios" />

                    <x-data-table.filter-select
                        label="Estado" filterKey="status"
                        :options="$statuses"
                        placeholder="Todos" />

                    <x-data-table.filter-select
                        label="Motivo de Descuadre" filterKey="difference_reason"
                        :options="$difference_reasons"
                        placeholder="Todos los motivos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Apertura" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($sessions as $session)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono text-slate-400">#{{ str_pad($session->id, 5, '0', STR_PAD_LEFT) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="terminal_id" :visible="$visibleColumns">
                    <div class="flex items-center font-medium text-slate-900">
                        <x-heroicon-s-computer-desktop class="w-4 h-4 mr-2 text-zertix-primary-500" />
                        {{ $session->terminal->name ?? 'N/A' }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="opened_by_user_id" :visible="$visibleColumns">
                    <div class="flex items-center text-slate-600">
                        <x-heroicon-s-arrow-up-circle class="w-4 h-4 mr-2 text-emerald-400" />
                        {{ $session->openedBy->name ?? 'N/A' }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="closed_by_user_id" :visible="$visibleColumns">
                    <div class="flex items-center text-slate-600">
                        <x-heroicon-s-arrow-down-circle class="w-4 h-4 mr-2 text-amber-400" />
                        {{ $session->closedBy->name ?? '—' }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @php
                        $sLabels = \App\Models\Sales\Pos\PosSession::getStatuses();
                        $sVariant = match($session->status) {
                            \App\Models\Sales\Pos\PosSession::STATUS_OPEN => 'success',
                            \App\Models\Sales\Pos\PosSession::STATUS_CLOSED => 'slate',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$sVariant" size="sm" :dot="false">
                        {{ $sLabels[$session->status] ?? $session->status }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="opened_at" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $session->opened_at->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $session->opened_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="closed_at" :visible="$visibleColumns">
                    @if($session->closed_at)
                        <span class="block text-xs font-medium text-slate-700">{{ $session->closed_at->format('d/m/Y') }}</span>
                        <span class="text-[10px] text-slate-400">{{ $session->closed_at->format('h:i A') }}</span>
                    @else
                        <span class="text-slate-300 italic text-xs">En curso...</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="opening_balance" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="text-[10px] text-slate-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($session->opening_balance, 2) }}
                </x-data-table.cell>

                <x-data-table.cell column="expected_balance" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_CLOSED)
                        <span class="text-[10px] text-slate-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($session->expected_balance, 2) }}
                    @else
                        <span class="text-slate-300 italic text-[10px]">Calculando...</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="closing_balance" :visible="$visibleColumns" class="px-4 py-3.5 text-right font-bold text-slate-900">
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_CLOSED)
                        <span class="text-[10px] font-normal text-slate-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($session->closing_balance, 2) }}
                    @else
                        <span class="text-slate-300">---</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="difference" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_CLOSED)
                        <span class="{{ $session->difference >= 0 ? ($session->difference == 0 ? 'text-slate-500' : 'text-emerald-600') : 'text-red-600' }} font-bold">
                            <span class="text-[10px] font-normal mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($session->difference, 2) }}
                        </span>
                    @else
                        <span class="text-slate-300">---</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="notes" :visible="$visibleColumns" class="px-4 py-3.5 max-w-[150px] truncate">
                    <span class="text-xs text-slate-400 italic" title="{{ $session->notes }}">{{ $session->notes ?? 'Sin observaciones' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-400">{{ $session->created_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.action-menu>
                            <x-ui.action-menu.item href="{{ route('sales.pos.sessions.show', $session) }}" icon="heroicon-o-document-chart-bar">
                                Ver Reporte Detallado
                            </x-ui.action-menu.item>

                            @if($session->status === \App\Models\Sales\Pos\PosSession::STATUS_OPEN && auth()->user()->can('pos sessions manage'))
                                <x-ui.action-menu.item href="{{ route('sales.pos.sessions.close-form', $session) }}" icon="heroicon-o-lock-closed">
                                    Realizar Arqueo y Cierre
                                </x-ui.action-menu.item>
                            @endif
                        </x-ui.action-menu>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-calculator" title="No se encontraron sesiones registradas"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    {{-- MODAL DE APERTURA --}}
    @include('sales.pos.sessions.partials.modal-open')
</div>
