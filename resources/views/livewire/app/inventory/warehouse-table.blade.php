<div>

    <x-ui.page-header
        title="Gestión de Almacenes"
        description="Gestiona los almacenes donde se distribuye tu inventario."
        :count="$warehouses->total()"
        countLabel="almacenes"
    >
        <x-slot:actions>
            @can('configure warehouses')
                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-warehouse')">
                    Nuevo Almacén
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @can('configure warehouses')
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
        :items="$warehouses"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Tipo de Almacén" filterKey="type"
                    :options="$types"
                    placeholder="Todos los tipos" />

                <x-data-table.filter-select
                    label="Estado" filterKey="is_active"
                    :options="['1' => 'Activos', '0' => 'Inactivos']"
                    placeholder="Todos" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($warehouses as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-medium text-slate-900">{{ $item->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="types" :visible="$visibleColumns">
                    <span class="flex items-center gap-1 text-slate-600">
                        @if($item->type === \App\Models\Inventory\Warehouse::TYPE_MOBILE)
                            <x-heroicon-s-truck class="w-4 h-4 text-amber-500" />
                        @elseif($item->type === \App\Models\Inventory\Warehouse::TYPE_POS)
                            <x-heroicon-s-shopping-cart class="w-4 h-4 text-blue-500" />
                        @else
                            <x-heroicon-s-building-office class="w-4 h-4 text-slate-500" />
                        @endif
                        {{ $item->type_label }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="accounting_account_id" :visible="$visibleColumns">
                    @if($item->accountingAccount)
                        <div class="flex flex-col">
                            <span class="text-xs font-mono font-bold text-zertix-primary-600 bg-zertix-primary-50 px-2 py-0.5 rounded w-fit">
                                {{ $item->accountingAccount->code }}
                            </span>
                            <span class="text-[10px] text-slate-400 truncate max-w-[150px]" title="{{ $item->accountingAccount->name }}">
                                {{ $item->accountingAccount->name }}
                            </span>
                        </div>
                    @else
                        <span class="text-xs text-amber-500 italic flex items-center gap-1">
                            <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                            Sin vincular
                        </span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="address" :visible="$visibleColumns" class="px-4 py-3.5 max-w-xs truncate">
                    <span class="text-slate-500">{{ $item->address ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="description" :visible="$visibleColumns">
                    <span class="text-slate-400 italic">{{ $item->description ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <x-ui.badge :variant="$item->is_active ? 'success' : 'error'" size="sm">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
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
                        @if($item->trashed())
                            <x-ui.button
                                appearance="ghost" variant="success" size="sm" icon="heroicon-o-arrow-path"
                                wire:click="restore({{ $item->id }})"
                                aria-label="Restaurar almacén" title="Restaurar almacén" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.button
                                appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                                x-data @click="$dispatch('open-modal', 'view-warehouse-{{ $item->id }}')"
                                aria-label="Ver detalles" title="Ver detalles" />

                            <x-ui.action-menu>
                                <x-ui.action-menu.item
                                    x-data @click="$dispatch('open-modal', 'edit-warehouse-{{ $item->id }}')"
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
                    <x-ui.empty-state variant="simple" icon="heroicon-o-building-office" title="No hay almacenes registrados"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('inventory.warehouses.partials.modals', ['warehouses' => $warehouses, 'types' => $types])
</div>
