<div>

    <x-ui.page-header
        title="Categorías de productos"
        description="Las principales agrupan; los productos van en sus subcategorías."
        :count="$categories->total()"
        countLabel="categorías"
    >
        <x-slot:actions>
            @can('categories.manage')
                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" href="{{ route('configuration.categories.create') }}">
                    Nueva Categoría
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @can('categories.manage')
        <div class="flex gap-1 mb-4">
            <x-ui.button
                size="sm"
                :variant="$filters['trashed'] === '' ? 'primary' : 'secondary'"
                :appearance="$filters['trashed'] === '' ? 'solid' : 'ghost'"
                wire:click="$set('filters.trashed', '')">
                Activas
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
        :items="$categories"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-select
                    label="Categoría principal" filterKey="parent_id"
                    :options="$parents"
                    placeholder="Todas" />

                <x-data-table.filter-select
                    label="Estado" filterKey="is_active"
                    :options="['1' => 'Activas', '0' => 'Inactivas']"
                    placeholder="Todos" />
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($categories as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono text-slate-500">{{ $item->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    @if($item->isParent())
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-folder class="w-4 h-4 text-slate-400 shrink-0" />
                            <span class="font-semibold text-slate-900 whitespace-nowrap">{{ $item->name }}</span>
                            <span class="text-xs text-slate-400 whitespace-nowrap">{{ $item->children_count }} {{ $item->children_count === 1 ? 'subcategoría' : 'subcategorías' }}</span>
                        </div>
                    @else
                        <div class="flex items-center gap-2 pl-6">
                            <x-heroicon-o-arrow-turn-down-right class="w-4 h-4 text-slate-300 shrink-0" />
                            <span class="text-slate-800 whitespace-nowrap">{{ $item->name }}</span>
                            @if($filters['search'] !== '' || $item->trashed() || $item->parent?->trashed())
                                <span class="text-xs text-slate-400">en {{ $item->parent?->name }}{{ $item->parent?->trashed() ? ' (papelera)' : '' }}</span>
                            @endif
                        </div>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="products_count" :visible="$visibleColumns">
                    @if($item->isParent())
                        <span class="text-slate-300">—</span>
                    @else
                        <span class="font-mono text-slate-700">{{ $item->products_count }}</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->is_active ? 'success' : 'error'" size="sm" :dot="false">
                        {{ $item->is_active ? 'Activa' : 'Inactiva' }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="description" :visible="$visibleColumns">
                    <span class="text-slate-600">{{ $item->description ?? '—' }}</span>
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
                                aria-label="Restaurar categoría" title="Restaurar categoría" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.action-menu>
                                <x-ui.action-menu.item
                                    href="{{ route('configuration.categories.edit', $item) }}"
                                    icon="heroicon-o-pencil-square">
                                    Editar
                                </x-ui.action-menu.item>
                                <x-ui.action-menu.item
                                    wire:click="toggleActivo({{ $item->id }})"
                                    icon="heroicon-o-power">
                                    {{ $item->is_active ? 'Desactivar' : 'Activar' }}
                                </x-ui.action-menu.item>
                                <x-ui.action-menu.item
                                    x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                    icon="heroicon-o-trash" variant="danger">
                                    Eliminar
                                </x-ui.action-menu.item>
                            </x-ui.action-menu>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" title="No hay categorías registradas"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('products.categories.partials.modals', ['categories' => $categories])
</div>
