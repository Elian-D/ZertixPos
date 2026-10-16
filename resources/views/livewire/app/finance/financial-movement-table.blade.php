@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
@endphp

<div>
    <x-ui.page-header
        title="Movimientos de Tesorería"
        description="Todo lo que entra y sale de las cajas y bancos. El saldo de cada fila es el que quedó en su cuenta."
        :count="$movements->total()"
        countLabel="movimientos"
    >
        @can('financial_accounts.move')
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.treasury.movements.create', array_filter(['account' => $filters['account_id'] ?: null])) }}"
                    variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo movimiento
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$movements"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Cuenta" filterKey="account_id" :options="$accounts" placeholder="Todas" />
                    <x-data-table.filter-select label="Origen" filterKey="origin" :options="$origins" placeholder="Todos" />
                    <x-data-table.filter-select label="Dirección" filterKey="direction" :options="$directions" placeholder="Todas" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha del movimiento" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($movements as $movement)
            <tr @class(['hover:bg-slate-50 transition-colors duration-150', 'opacity-60' => $movement->isReversed()]) wire:key="movement-{{ $movement->id }}">
                <x-data-table.cell column="movement_date" :visible="$visibleColumns">
                    <span class="text-xs font-medium text-slate-700">{{ $movement->movement_date->format('d/m/Y') }}</span>
                    <span class="block text-[11px] text-slate-400">{{ $movement->created_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="account" :visible="$visibleColumns">
                    <a href="{{ route('finance.treasury.show', $movement->financial_account_id) }}" class="font-medium text-slate-800 hover:underline">
                        {{ $movement->account->name ?? '—' }}
                    </a>
                </x-data-table.cell>

                <x-data-table.cell column="origin" :visible="$visibleColumns">
                    <x-ui.badge :variant="$movement->origin_variant" size="sm" :dot="false">{{ $movement->origin_label }}</x-ui.badge>
                    @if($movement->isReversed())
                        <span class="block mt-1 text-[11px] font-medium text-state-error">Anulado</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="detail" :visible="$visibleColumns">
                    @if($movement->detail)
                        <span class="block text-xs font-medium text-slate-700">{{ $movement->detail }}</span>
                    @endif
                    <span class="block text-xs text-slate-500 max-w-xs truncate">{{ $movement->notes ?? ($movement->detail ? '' : '—') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="in" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    @if($movement->isIn())
                        <span class="font-semibold tabular-nums text-state-success">{{ $money($movement->amount) }}</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="out" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    @unless($movement->isIn())
                        <span class="font-semibold tabular-nums text-state-error">{{ $money($movement->amount) }}</span>
                    @endunless
                </x-data-table.cell>

                <x-data-table.cell column="signed" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-semibold tabular-nums', 'text-state-success' => $movement->isIn(), 'text-state-error' => ! $movement->isIn()])>
                        {{ $movement->isIn() ? '+' : '−' }}{{ $money($movement->amount) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="balance_after" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-bold tabular-nums', 'text-state-error' => $movement->balance_after < 0, 'text-slate-900' => $movement->balance_after >= 0])>
                        {{ $money($movement->balance_after) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="creator" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $movement->creator->name ?? 'Sistema' }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    {{-- Única acción de la fila: botón directo, igual que en el show de la cuenta --}}
                    <x-ui.button variant="secondary" appearance="ghost" size="sm" iconLeft="heroicon-o-eye"
                        x-data x-on:click="$dispatch('open-modal', 'view-movement-{{ $movement->id }}')">
                        Ver
                    </x-ui.button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-queue-list" title="Sin movimientos"
                        description="No hay movimientos con estos filtros." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    @include('finance.treasury.movements.partials.detail-modals', ['movements' => $movements])
</div>
