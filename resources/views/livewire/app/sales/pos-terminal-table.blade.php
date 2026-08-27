<div>

    <x-ui.page-header
        title="Configuración de Terminales POS"
        description="Administra las terminales de punto de venta registradas y su configuración."
        :count="$terminals->total()"
        countLabel="terminales"
    >
        <x-slot:actions>
            @can('create pos terminals')
                <x-ui.button href="{{ route('sales.pos.terminals.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nueva Terminal
                </x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:secondary>
            @can('pos config view')
                <x-ui.button href="{{ route('sales.pos.settings.edit') }}"
                    appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-o-cog">
                    Configuración Global
                </x-ui.button>
            @endcan
        </x-slot:secondary>
    </x-ui.page-header>

    @can('view pos terminals')
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
        :items="$terminals"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        @forelse($terminals as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-slate-700">{{ $item->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <div class="flex items-center font-medium text-slate-900">
                        @if($item->is_mobile)
                            <x-heroicon-s-device-phone-mobile class="w-4 h-4 mr-2 text-zertix-primary-500" title="Dispositivo Móvil" />
                        @else
                            <x-heroicon-s-computer-desktop class="w-4 h-4 mr-2 text-slate-400" title="Terminal Fija" />
                        @endif
                        {{ $item->name }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="warehouse_id" :visible="$visibleColumns">
                    <span class="whitespace-nowrap px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium">
                        {{ $item->warehouse->name ?? 'No asignado' }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="cash_account_id" :visible="$visibleColumns">
                    <div class="flex flex-col">
                        <span class="text-xs font-mono text-slate-400">{{ $item->cashAccount->code ?? '' }}</span>
                        <span>{{ $item->cashAccount->name ?? '—' }}</span>
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="default_ncf_type_id" :visible="$visibleColumns">
                    @if(!general_config()?->esModoFiscal())
                        <span class="text-slate-300 italic text-xs">No Fiscal</span>
                    @else
                        {{ $item->defaultNcfType->name ?? 'Global' }}
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="default_client_id" :visible="$visibleColumns">
                    {{ $item->defaultClient->name ?? pos_config('default_walkin_customer_name') ?? 'Consumidor Final' }}
                    @if(is_null($item->default_client_id))
                        <span class="text-[9px] text-blue-500 font-bold ml-1">(HEREDADO)</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="is_mobile" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-heroicon-s-check-circle class="w-5 h-5 mx-auto {{ $item->is_mobile ? 'text-emerald-500' : 'text-slate-200' }}" />
                </x-data-table.cell>

                <x-data-table.cell column="printer_format" :visible="$visibleColumns">
                    @php $isInherited = is_null($item->printer_format); @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $isInherited ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-slate-100 text-slate-800 border-slate-200' }}">
                        <x-heroicon-s-printer class="w-3 h-3 mr-1" />
                        {{ $item->printer_format ?? pos_config('receipt_size') }}
                        @if($isInherited)
                            <span class="ml-1 text-[9px] opacity-70">(H)</span>
                        @endif
                    </span>
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
                                aria-label="Restaurar terminal" title="Restaurar terminal" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            @can('view pos terminals')
                                <x-ui.button
                                    appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                                    x-data @click="$dispatch('open-modal', 'view-terminal-{{ $item->id }}')"
                                    aria-label="Ver detalles" title="Ver detalles" />
                            @endcan

                            <x-ui.action-menu>
                                @can('edit pos terminals')
                                    <x-ui.action-menu.item href="{{ route('sales.pos.terminals.edit', $item) }}" icon="heroicon-o-pencil-square">
                                        Editar
                                    </x-ui.action-menu.item>
                                @endcan
                                @can('delete pos terminals')
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
                    <x-ui.empty-state variant="simple" icon="heroicon-o-computer-desktop" title="No se encontraron terminales"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('sales.pos.terminals.partials.modals', ['items' => $terminals])
</div>
