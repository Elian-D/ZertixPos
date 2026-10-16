@php $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.'); @endphp

<div>
    <x-ui.page-header
        title="Transferencias"
        :description="$inTransit > 0 ? $inTransit.' '.($inTransit === 1 ? 'transferencia en tránsito' : 'transferencias en tránsito').', pendientes de recibir.' : 'Mueve mercancía entre almacenes: sale del origen al enviar y entra al destino al recibir.'"
        :count="$transfers->total()"
        countLabel="transferencias"
    >
        @can('inventory_transfers.create')
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.transfers.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nueva transferencia
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$transfers"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Desde" filterKey="from_warehouse_id" :options="$warehouses" placeholder="Todos" />
                    <x-data-table.filter-select label="Hacia" filterKey="to_warehouse_id" :options="$warehouses" placeholder="Todos" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha de creación" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($transfers as $transfer)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="transfer-{{ $transfer->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('inventory.transfers.show', $transfer) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $transfer->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="route" :visible="$visibleColumns">
                    <span class="inline-flex items-center gap-1.5 text-sm text-slate-800">
                        <span class="font-medium">{{ $transfer->fromWarehouse->name ?? '—' }}</span>
                        <x-heroicon-s-arrow-right class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span class="font-medium">{{ $transfer->toWarehouse->name ?? '—' }}</span>
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="items" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600 tabular-nums">{{ $transfer->items_count }} · {{ $fmtQty($transfer->quantity_sent_total) }} u.</span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$transfer->status_variant" size="sm" :dot="false">{{ $transfer->status_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="sent_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $transfer->sent_at?->format('d/m/Y h:i A') ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="received_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $transfer->received_at?->format('d/m/Y h:i A') ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="creator" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $transfer->creator->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs font-medium text-slate-700">{{ $transfer->created_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        <x-ui.action-menu.item href="{{ route('inventory.transfers.show', $transfer) }}" icon="heroicon-o-eye">
                            Ver
                        </x-ui.action-menu.item>
                        @if($transfer->isDraft())
                            @can('inventory_transfers.create')
                                <x-ui.action-menu.item href="{{ route('inventory.transfers.edit', $transfer) }}" icon="heroicon-o-pencil-square">
                                    Editar
                                </x-ui.action-menu.item>
                            @endcan
                        @endif
                        @if($transfer->isSent())
                            @can('inventory_transfers.receive')
                                <x-ui.action-menu.item href="{{ route('inventory.transfers.receive.form', $transfer) }}" icon="heroicon-o-inbox-arrow-down">
                                    Recibir
                                </x-ui.action-menu.item>
                            @endcan
                        @endif
                        <x-ui.action-menu.item href="{{ route('inventory.transfers.pdf', $transfer) }}" target="_blank" icon="heroicon-o-printer">
                            PDF
                        </x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-arrows-right-left" title="No hay transferencias"
                        description="Crea una para mover mercancía de un almacén a otro." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
