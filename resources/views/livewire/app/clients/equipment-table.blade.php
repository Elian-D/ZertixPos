<div>

    <x-ui.page-header
        title="Gestión de Equipos"
        description="Gestiona el equipo instalado en las instalaciones de tus clientes."
        :count="$equipments->total()"
        countLabel="equipos"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('clients.equipment.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                Nuevo Equipo
            </x-ui.button>
        </x-slot:actions>

        <x-slot:secondary>
            <x-ui.button
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                wire:click="export">
                Exportar (Excel)
            </x-ui.button>
        </x-slot:secondary>
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
        :items="$equipments"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Tipo de Equipo" filterKey="equipment_type_id"
                    :options="$equipmentTypes->pluck('nombre', 'id')->all()"
                    placeholder="Todos" />

                <x-data-table.filter-select
                    label="Punto de Venta" filterKey="point_of_sale_id"
                    :options="$pointsOfSale->pluck('name', 'id')->all()"
                    placeholder="Todos" />

                <x-data-table.filter-select
                    label="Estado" filterKey="active"
                    :options="['1' => 'Activos', '0' => 'Inactivos']"
                    placeholder="Todos" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($equipments as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="code" :visible="$visibleColumns">
                    <span class="font-mono text-slate-500">{{ $item->code }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $item->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="equipment_type_id" :visible="$visibleColumns">
                    {{ $item->equipmentType->nombre ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="point_of_sale_id" :visible="$visibleColumns">
                    {{ $item->pointOfSale->name ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="serial_number" :visible="$visibleColumns">
                    {{ $item->serial_number ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="model" :visible="$visibleColumns">
                    {{ $item->model ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="active" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->active ? 'success' : 'error'" size="sm" :dot="false">
                        {{ $item->active ? 'Activo' : 'Inactivo' }}
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
                                aria-label="Restaurar equipo" title="Restaurar equipo" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.button
                                appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                                x-data @click="$dispatch('open-modal', 'view-equipment-{{ $item->id }}')"
                                aria-label="Ver detalles" title="Ver detalles" />

                            <x-ui.action-menu>
                                <x-ui.action-menu.item href="{{ route('clients.equipment.edit', $item) }}" icon="heroicon-o-pencil-square">
                                    Editar
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
                        description="No hay equipos registrados con los filtros aplicados." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('clients.equipment.partials.modals', ['equipments' => $equipments])
</div>
