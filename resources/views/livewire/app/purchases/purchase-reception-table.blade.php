@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp

<div>
    <x-ui.page-header title="Recepciones"
        :description="'Recibido con los filtros actuales: '.$money($receivedTotal).'. Se registran desde cada orden de compra.'"
        :count="$receptions->total()" countLabel="recepciones" />

    <x-data-table.base-table
        :items="$receptions"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Proveedor" filterKey="vendor_id" :options="$vendors" placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha de recepción" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($receptions as $reception)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="reception-{{ $reception->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('purchases.receptions.show', $reception) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $reception->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="reception_date" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $reception->reception_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="vendor" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $reception->order->vendor?->display_name ?? '—' }}</span>
                    <span class="block text-xs text-slate-500">{{ $reception->order->warehouse->name ?? '' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="order" :visible="$visibleColumns">
                    <a href="{{ route('purchases.orders.show', $reception->purchase_order_id) }}" class="font-mono text-xs font-semibold text-zertix-primary-700 hover:underline">{{ $reception->order->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="invoice" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $reception->supplier_invoice ?? $reception->supplier_ncf ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="quantities" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700 tabular-nums">{{ $fmtQty($reception->quantity_received_total) }} u.</span>
                    @if($reception->quantity_rejected_total > 0)
                        <span class="block text-xs text-state-error tabular-nums">{{ $fmtQty($reception->quantity_rejected_total) }} rechazadas</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="total" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="font-bold tabular-nums text-slate-900">{{ $money($reception->total) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="payable" :visible="$visibleColumns">
                    @if($reception->payable)
                        <span class="font-mono text-xs font-semibold text-slate-700">{{ $reception->payable->number }}</span>
                        <x-ui.badge :variant="$reception->payable->status_variant" size="sm" :dot="false" class="ml-1">{{ $reception->payable->status_label }}</x-ui.badge>
                    @else
                        <span class="text-xs text-slate-400">Sin deuda</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="rating" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @if($reception->rating)
                        <x-ui.badge :variant="$reception->rating_variant" size="sm" :dot="false">{{ $reception->rating }}</x-ui.badge>
                    @else
                        <span class="text-xs text-slate-400">—</span>
                    @endif
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.button href="{{ route('purchases.receptions.show', $reception) }}" variant="secondary" appearance="ghost" size="sm" iconLeft="heroicon-o-eye">
                        Ver
                    </x-ui.button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-inbox-arrow-down" title="No hay recepciones"
                        description="Se registran con Recibir, desde una orden de compra aprobada." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
