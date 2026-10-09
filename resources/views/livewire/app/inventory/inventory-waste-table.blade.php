@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
@endphp

<div>
    <x-ui.page-header
        title="Mermas"
        :description="'Valor perdido con los filtros actuales: '.$money($lostTotal).'. No incluye las anuladas.'"
        :count="$wastes->total()"
        countLabel="mermas"
    >
        @can('inventory_wastes.create')
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.wastes.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Registrar merma
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$wastes"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Almacén" filterKey="warehouse_id" :options="$warehouses" placeholder="Todos" />
                    <x-data-table.filter-select label="Motivo" filterKey="reason" :options="$reasons" placeholder="Todos" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha de la merma" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($wastes as $waste)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="waste-{{ $waste->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('inventory.wastes.show', $waste) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $waste->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="waste_date" :visible="$visibleColumns">
                    <span class="text-xs font-medium text-slate-700">{{ $waste->waste_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="warehouse" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $waste->warehouse->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="reasons" :visible="$visibleColumns">
                    <span class="inline-flex flex-wrap gap-1">
                        @foreach($waste->items->pluck('reason')->unique() as $reason)
                            <x-ui.badge variant="slate" size="sm" :dot="false">{{ $reasons[$reason] ?? $reason }}</x-ui.badge>
                        @endforeach
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="items" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600 tabular-nums">{{ $waste->items_count }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="total_value" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-bold tabular-nums', 'text-state-error' => $waste->isApplied(), 'text-slate-400 line-through' => $waste->isVoided()])>
                        {{ $money($waste->total_value) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$waste->status_variant" size="sm" :dot="false">{{ $waste->status_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="creator" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $waste->creator->name ?? '—' }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        <x-ui.action-menu.item href="{{ route('inventory.wastes.show', $waste) }}" icon="heroicon-o-eye">
                            Ver
                        </x-ui.action-menu.item>
                        <x-ui.action-menu.item href="{{ route('inventory.wastes.pdf', $waste) }}" target="_blank" icon="heroicon-o-printer">
                            PDF
                        </x-ui.action-menu.item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-archive-box-x-mark" title="No hay mermas registradas"
                        description="Registra la mercancía dañada, vencida, robada o consumida para darla de baja." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
