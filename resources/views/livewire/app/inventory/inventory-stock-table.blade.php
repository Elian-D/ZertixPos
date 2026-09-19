<div>

    <x-ui.page-header
        title="Estado Actual de Inventario"
        description="Consulta las existencias actuales de tus productos por almacén."
        :count="$stocks->total()"
        countLabel="existencias"
    >
        <x-slot:secondary>
            <x-ui.button
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                wire:click="export">
                Exportar Inventario
            </x-ui.button>
        </x-slot:secondary>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$stocks"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Ubicación/Almacén" filterKey="warehouse_id"
                    :options="$warehouses->pluck('name', 'id')->all()"
                    placeholder="Todas las ubicaciones" />

                <x-data-table.filter-select
                    label="Categoría" filterKey="category_id"
                    :options="$categories->pluck('name', 'id')->all()"
                    placeholder="Todas las categorías" />

                <x-data-table.filter-select
                    label="Estado de Stock" filterKey="status"
                    :options="['ok' => 'Stock Suficiente', 'low' => 'Stock Bajo', 'out' => 'Agotado']"
                    placeholder="Cualquier estado" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($stocks as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="product_id" :visible="$visibleColumns">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8 bg-slate-100 rounded-md flex items-center justify-center">
                            @if($item->product->image_path)
                                <img class="h-8 w-8 rounded-md object-cover" src="{{ $item->product->image_url }}" alt="">
                            @else
                                <x-heroicon-o-cube class="w-5 h-5 text-slate-400" />
                            @endif
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-semibold text-slate-900">{{ $item->product->name }}</div>
                            <div class="text-xs text-slate-500">{{ $item->product->category->name ?? '—' }}</div>
                        </div>
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="warehouse_id" :visible="$visibleColumns">
                    <span class="text-slate-600 font-medium">{{ $item->warehouse->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="quantity" :visible="$visibleColumns">
                    <div class="font-black text-slate-900">
                        {{ number_format($item->quantity, 2) }}
                        <span class="text-[10px] text-slate-400 font-normal uppercase italic">{{ $item->product->unit->abbreviation ?? '' }}</span>
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="min_stock" :visible="$visibleColumns">
                    <span class="text-slate-500">{{ number_format($item->min_stock, 2) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns">
                    @php
                        $status = 'ok';
                        if ($item->quantity <= 0) $status = 'out';
                        elseif ($item->quantity <= $item->min_stock) $status = 'low';
                    @endphp
                    <x-ui.badge :variant="match($status) { 'ok' => 'success', 'low' => 'warning', default => 'error' }" size="sm">
                        {{ match($status) { 'ok' => 'Suficiente', 'low' => 'Stock Bajo', default => 'Agotado' } }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $item->created_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $item->updated_at->diffForHumans() }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button
                            appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                            x-data @click="$dispatch('open-modal', 'view-stock-{{ $item->id }}')"
                            aria-label="Ver detalles" title="Ver detalles" />

                        @can('inventory_stocks.update')
                            <x-ui.button
                                appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-bell-alert"
                                x-data @click="$dispatch('open-modal', 'edit-min-stock-{{ $item->id }}')"
                                aria-label="Ajustar stock mínimo" title="Ajustar Stock Mínimo" />
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-building-office" title="No hay registros"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('inventory.stocks.partials.modals', ['stocks' => $stocks])
</div>
