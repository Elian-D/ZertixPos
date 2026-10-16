@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
@endphp

<div>
    <x-ui.page-header title="Cuentas por Pagar" :description="'Pendiente con los filtros actuales: '.$money($pendingTotal).'.'"
        :count="$payables->total()" countLabel="cuentas">
        @can('expenses.create')
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.expenses.create') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-plus">
                    Registrar gasto
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$payables"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Proveedor" filterKey="vendor_id" :options="$vendors" placeholder="Todos" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                    <x-data-table.filter-select label="Origen" filterKey="source_type" :options="$origins" placeholder="Todos" />
                    <x-data-table.filter-toggle label="Solo vencidas" filterKey="overdue" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Vencimiento" collapsed>
                    <x-data-table.filter-date-range label="Fecha de vencimiento" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($payables as $payable)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="payable-{{ $payable->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('finance.payables.show', $payable) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $payable->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="vendor" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $payable->vendor?->display_name ?? 'Sin proveedor' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="concept" :visible="$visibleColumns">
                    <span class="block text-xs text-slate-700 max-w-xs truncate">{{ $payable->concept }}</span>
                    @if($payable->supplier_invoice)<span class="block text-[11px] text-slate-400">{{ $payable->supplier_invoice }}</span>@endif
                </x-data-table.cell>

                <x-data-table.cell column="origin" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $payable->origin['label'] ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="issue_date" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $payable->issue_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="due_date" :visible="$visibleColumns">
                    <span @class(['text-xs font-medium', 'text-state-error' => $payable->isOverdue(), 'text-slate-700' => ! $payable->isOverdue()])>
                        {{ $payable->due_date->format('d/m/Y') }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="total_amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="tabular-nums text-slate-700">{{ $money($payable->total_amount) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="balance" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-bold tabular-nums', 'text-state-warning' => $payable->isOpen(), 'text-slate-400' => ! $payable->isOpen()])>
                        {{ $money($payable->balance) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$payable->status_variant" size="sm" :dot="false">{{ $payable->status_label }}</x-ui.badge>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        <x-ui.action-menu.item href="{{ route('finance.payables.show', $payable) }}" icon="heroicon-o-eye">Ver</x-ui.action-menu.item>
                        @if($payable->isOpen())
                            @can('payables.pay')
                                <x-ui.action-menu.item href="{{ route('finance.payables.pay.form', $payable) }}" icon="heroicon-o-banknotes">Pagar</x-ui.action-menu.item>
                            @endcan
                        @endif
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-document-text" title="No hay cuentas por pagar"
                        description="Nacen al registrar un gasto a crédito o, más adelante, al recibir una compra a crédito." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
