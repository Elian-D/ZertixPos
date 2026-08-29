<div>

    <x-ui.page-header
        title="Gestión de Puntos de Venta"
        description="Administra los puntos de venta y ubicaciones de entrega asociados a tus clientes."
        :count="$pos->total()"
        countLabel="puntos de venta"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('clients.delivery_points.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                Nuevo Punto de Venta
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
        :items="$pos"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Cliente (Cuenta)" filterKey="client"
                    :options="$clients->pluck('name', 'id')->all()"
                    placeholder="Todos los clientes" />

                <x-data-table.filter-select
                    label="Estado Operativo" filterKey="active"
                    :options="['1' => 'Activos', '0' => 'Inactivos']"
                    placeholder="Todos" />

                <x-data-table.filter-select
                    label="Tipo de Negocio" filterKey="business_type"
                    :options="$businessTypes->pluck('nombre', 'id')->all()"
                    placeholder="Todos los tipos" />

                <x-data-table.filter-select
                    label="Provincia" filterKey="state"
                    :options="$states->pluck('name', 'id')->all()"
                    placeholder="Todas las provincias" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($pos as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $item->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="client_id" :visible="$visibleColumns">
                    {{ $item->client->name ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="business_type_id" :visible="$visibleColumns">
                    {{ $item->businessType->nombre ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="provincia_id" :visible="$visibleColumns">
                    {{ $item->provincia->name ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="city" :visible="$visibleColumns">
                    {{ $item->city ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="contact_name" :visible="$visibleColumns">
                    {{ $item->contact_name ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="contact_phone" :visible="$visibleColumns">
                    {{ $item->contact_phone ?? '—' }}
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
                                aria-label="Restaurar punto de venta" title="Restaurar punto de venta" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.button
                                appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                                x-data @click="$dispatch('open-modal', 'view-pos-{{ $item->id }}')"
                                aria-label="Ver detalles completos" title="Ver detalles completos" />

                            <x-ui.action-menu>
                                <x-ui.action-menu.item href="{{ route('clients.delivery_points.edit', $item) }}" icon="heroicon-o-pencil-square">
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
                        description="No hay puntos de venta registrados con los filtros aplicados." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('clients.pos.partials.modals', ['pos' => $pos])
</div>
