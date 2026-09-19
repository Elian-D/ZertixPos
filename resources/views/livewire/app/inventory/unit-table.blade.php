<div>

    <x-ui.page-header
        title="Gestión de Unidades de Medidas"
        description="Gestiona las unidades de medida utilizadas para tus productos e inventario."
        :count="$units->total()"
        countLabel="unidades"
    >
        <x-slot:actions>
            @can('units.manage')
                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-unit')">
                    Nueva Unidad de Medida
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @can('units.manage')
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
        :items="$units"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-select
                    label="Estado" filterKey="is_active"
                    :options="['1' => 'Activos', '0' => 'Inactivos']"
                    placeholder="Todos" />
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($units as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono text-slate-500">{{ $item->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-900">{{ $item->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="abbreviation" :visible="$visibleColumns">
                    <span class="text-slate-600">{{ $item->abbreviation ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->is_active ? 'success' : 'error'" size="sm" :dot="false">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
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
                                aria-label="Restaurar unidad" title="Restaurar unidad" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.action-menu>
                                <x-ui.action-menu.item
                                    x-data @click="$dispatch('open-modal', 'edit-unit-{{ $item->id }}')"
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
                    <x-ui.empty-state variant="simple" title="No hay unidades de medida registradas"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('products.units.partials.modals', ['units' => $units])
</div>
