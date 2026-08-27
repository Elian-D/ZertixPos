<div>

    <x-ui.page-header
        title="Gestión de Productos/Servicios"
        description="Gestiona el catálogo de productos y servicios, sus precios e inventario."
        :count="$products->total()"
        countLabel="productos"
    >
        <x-slot:actions>
            @can('create products')
                <x-ui.button href="{{ route('inventory.products.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo Producto/Servicio
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @can('restore products')
        <div class="flex gap-1 mb-4">
            <x-ui.button
                size="sm"
                :variant="$filters['trashed'] === '' ? 'primary' : 'secondary'"
                :appearance="$filters['trashed'] === '' ? 'solid' : 'ghost'"
                wire:click="$set('filters.trashed', '')">
                Activos
            </x-ui.button>

            <x-ui.button
                size="sm"
                :variant="$filters['trashed'] === 'only' ? 'error' : 'secondary'"
                :appearance="$filters['trashed'] === 'only' ? 'solid' : 'ghost'"
                wire:click="$set('filters.trashed', 'only')">
                Papelera
            </x-ui.button>
        </div>
    @endcan

    <x-data-table.base-table
        :items="$products"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Categoría" filterKey="category_id"
                    :options="$categories->pluck('name', 'id')->all()"
                    placeholder="Todas las categorías" />

                <x-data-table.filter-select
                    label="Unidad de Medida" filterKey="unit_id"
                    :options="$units->pluck('name', 'id')->all()"
                    placeholder="Todas las unidades" />

                <x-data-table.filter-select
                    label="Estado" filterKey="is_active"
                    :options="['1' => 'Activos', '0' => 'Inactivos']"
                    placeholder="Todos" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($products as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-900">{{ $item->name }}</span>
                    @if($item->sku)
                        <span class="block text-[10px] font-mono text-slate-400 mt-0.5">{{ $item->sku }}</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="image_path" :visible="$visibleColumns">
                    @if($item->image_path)
                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover shadow-sm">
                    @else
                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                            <x-heroicon-s-photo class="w-6 h-6" />
                        </div>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="category_id" :visible="$visibleColumns">
                    <span class="px-2 py-1 bg-slate-100 rounded text-xs">{{ $item->category->name ?? 'S/C' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="description" :visible="$visibleColumns" class="px-4 py-3.5 max-w-xs truncate">
                    <span class="text-slate-600">{{ $item->description ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="price_with_tax" :visible="$visibleColumns" class="px-4 py-3.5">
                    <span class="font-bold text-slate-900">{{ config('regional.currency_symbol') }} {{ number_format($item->price_with_tax, 2) }}</span>
                    @if($item->taxRate() > 0)
                        <span class="block text-[10px] text-slate-400 mt-0.5">Neto: {{ config('regional.currency_symbol') }} {{ number_format($item->price, 2) }}</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="price" :visible="$visibleColumns" class="px-4 py-3.5 font-bold text-slate-900">
                    {{ config('regional.currency_symbol') }} {{ number_format($item->price, 2) }}
                </x-data-table.cell>

                <x-data-table.cell column="cost" :visible="$visibleColumns" class="px-4 py-3.5 font-bold text-slate-900">
                    {{ config('regional.currency_symbol') }} {{ number_format($item->cost, 2) }}
                </x-data-table.cell>

                <x-data-table.cell column="unit_id" :visible="$visibleColumns">
                    {{ $item->unit->name ?? '—' }} ({{ $item->unit->abbreviation ?? '' }})
                </x-data-table.cell>

                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->is_active ? 'success' : 'error'" size="sm">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="is_stockable" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->is_stockable ? 'info' : 'warning'" size="sm" :dot="false">
                        {{ $item->is_stockable ? 'Producto' : 'Servicio' }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $item->created_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $item->updated_at->diffForHumans() }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if($item->trashed())
                            <x-ui.button
                                appearance="ghost" variant="success" size="sm" icon="heroicon-o-arrow-path"
                                wire:click="restore({{ $item->id }})"
                                aria-label="Restaurar producto" title="Restaurar producto" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.button
                                appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                                x-data @click="$dispatch('open-modal', 'view-product-{{ $item->id }}')"
                                aria-label="Ver detalles" title="Ver detalles" />

                            <x-ui.action-menu>
                                @can('edit products')
                                    <x-ui.action-menu.item href="{{ route('inventory.products.edit', $item) }}" icon="heroicon-o-pencil-square">
                                        Editar
                                    </x-ui.action-menu.item>
                                @endcan
                                @can('delete products')
                                    <x-ui.action-menu.item
                                        x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                        icon="heroicon-o-trash" variant="danger">
                                        Eliminar
                                    </x-ui.action-menu.item>
                                @endcan
                            </x-ui.action-menu>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" title="No hay productos que coincidan"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('products.partials.modals', ['products' => $products])
</div>
