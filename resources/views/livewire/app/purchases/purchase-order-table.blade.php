@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp

<div>
    <x-ui.page-header
        title="Órdenes de compra"
        :description="$toReceive > 0 ? $toReceive.' '.($toReceive === 1 ? 'orden pendiente' : 'órdenes pendientes').' de recibir.' : 'Lo que se le pide al proveedor. La deuda nace al recibir la mercancía, no al aprobar.'"
        :count="$orders->total()"
        countLabel="órdenes"
    >
        @can('purchase_orders.create')
            <x-slot:actions>
                <x-ui.button href="{{ route('purchases.orders.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nueva orden
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$orders"
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
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha de la orden" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($orders as $order)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="order-{{ $order->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('purchases.orders.show', $order) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $order->number }}</a>
                    @if($order->sent_at)
                        <span class="block text-[11px] text-slate-400">Enviada {{ $order->sent_at->format('d/m') }}</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="order_date" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $order->order_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="vendor" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $order->vendor?->display_name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="warehouse" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $order->warehouse->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="condition" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $order->condition_label }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="expected_date" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $order->expected_date?->format('d/m/Y') ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="items" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600 tabular-nums">{{ $order->items_count }} · {{ $fmtQty($order->quantity_total) }} u.</span>
                </x-data-table.cell>

                <x-data-table.cell column="total" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="font-bold tabular-nums text-slate-900">{{ $money($order->total) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$order->status_variant" size="sm" :dot="false">{{ $order->status_label }}</x-ui.badge>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        <x-ui.action-menu.item href="{{ route('purchases.orders.show', $order) }}" icon="heroicon-o-eye">
                            Ver
                        </x-ui.action-menu.item>
                        @if($order->isDraft())
                            @can('purchase_orders.create')
                                <x-ui.action-menu.item href="{{ route('purchases.orders.edit', $order) }}" icon="heroicon-o-pencil-square">
                                    Editar
                                </x-ui.action-menu.item>
                            @endcan
                        @endif
                        @if($order->canReceive())
                            @can('purchase_receptions.create')
                                <x-ui.action-menu.item href="{{ route('purchases.orders.receive.form', $order) }}" icon="heroicon-o-inbox-arrow-down">
                                    Recibir
                                </x-ui.action-menu.item>
                            @endcan
                        @endif
                        <x-ui.action-menu.item href="{{ route('purchases.orders.pdf', $order) }}" target="_blank" icon="heroicon-o-printer">
                            PDF
                        </x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-clipboard-document-list" title="No hay órdenes de compra"
                        description="Crea una para pedirle mercancía a un proveedor." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
