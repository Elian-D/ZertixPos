<div>

    <x-ui.page-header
        title="Gestión de Tipos de Negocios"
        description="Administra los tipos de negocio disponibles para clasificar a tus clientes."
        :count="$businessTypes->total()"
        countLabel="tipos de negocios"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-tipoNegocio')">
                Nuevo Tipo de Negocio
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

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

    <x-data-table.base-table
        :items="$businessTypes"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-select
                    label="Estado" filterKey="activo"
                    :options="['1' => 'Activos', '0' => 'Inactivos']"
                    placeholder="Todos" />
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($businessTypes as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono text-slate-500">{{ $item->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="nombre" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $item->nombre }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="prefix" :visible="$visibleColumns">
                    {{ $item->prefix ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="activo" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->activo ? 'success' : 'error'" size="sm" :dot="false">
                        {{ $item->activo ? 'Activo' : 'Inactivo' }}
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
                                aria-label="Restaurar tipo de negocio" title="Restaurar tipo de negocio" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.action-menu>
                                <x-ui.action-menu.item
                                    x-data @click="$dispatch('open-modal', 'edit-tipoNegocio-{{ $item->id }}')"
                                    icon="heroicon-o-pencil-square">
                                    Editar
                                </x-ui.action-menu.item>
                                <x-ui.action-menu.item
                                    wire:click="toggleActivo({{ $item->id }})"
                                    :icon="$item->activo ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'">
                                    {{ $item->activo ? 'Desactivar' : 'Activar' }}
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
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                    <x-ui.empty-state variant="simple" title="Sin resultados"
                        description="No hay tipos de negocio registrados con los filtros aplicados." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('clients.businessTypes.partials.modals', ['businessTypes' => $businessTypes])
</div>
