<div>

    <x-ui.page-header
        title="Gestión de Clientes"
        description="Gestiona la cartera de clientes, sus datos de contacto y condiciones comerciales."
        :count="$clients->total()"
        countLabel="clientes"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('clients.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                Nuevo Cliente
            </x-ui.button>
        </x-slot:actions>

        <x-slot:secondary>
            <x-ui.button
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                wire:click="export">
                Exportar (Excel)
            </x-ui.button>

            <x-ui.button href="{{ route('clients.import.view') }}" variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-up-tray">
                Importar clientes
            </x-ui.button>
        </x-slot:secondary>
    </x-ui.page-header>

    {{-- Papelera como tab del mismo índice, no una vista/ruta aparte
         (docs/analisis/politica-soft-deletes.md §6) — reusa tabla, columnas,
         orden y paginación en vez de duplicar table.blade.php+filters. --}}
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
        :items="$clients"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select
                        label="Tipo de Cliente" filterKey="type"
                        :options="['individual' => 'Individuales', 'company' => 'Empresas']"
                        placeholder="Todos" />

                    <x-data-table.filter-select
                        label="Estado del Cliente" filterKey="is_active"
                        :options="['1' => 'Activos', '0' => 'Inactivos']"
                        placeholder="Todos" />

                    <x-data-table.filter-select
                        label="Provincia" filterKey="state"
                        :options="$states->pluck('name', 'id')->all()"
                        placeholder="Todas" />

                    <x-data-table.filter-select
                        label="Tipo de ID Fiscal" filterKey="tax_type"
                        :options="$taxIdentifierTypes->pluck('label', 'value')->all()"
                        placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Estado Financiero" collapsed>
                    <x-data-table.filter-select
                        label="Saldo" filterKey="has_debt"
                        :options="['yes' => 'Con Deuda', 'no' => 'Sin Deuda']"
                        placeholder="Todos" />

                    <x-data-table.filter-select
                        label="Estado Crédito" filterKey="over_limit"
                        :options="['1' => 'Límite Excedido']"
                        placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Registro" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($clients as $client)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="text-slate-500">#{{ $client->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="text-slate-800 font-medium">{{ $client->commercial_name ?: $client->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="tax_identifier_types" :visible="$visibleColumns">
                    {{ $client->tax_label ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="tax_id" :visible="$visibleColumns">
                    {{ $client->tax_id ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="type" :visible="$visibleColumns">
                    {{ $client->type === 'company' ? 'Empresa' : 'Individual' }}
                </x-data-table.cell>

                <x-data-table.cell column="balance" :visible="$visibleColumns">
                    <span class="font-semibold {{ $client->balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ config('regional.currency_symbol') }}{{ number_format($client->balance, 2) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="credit_limit" :visible="$visibleColumns">
                    @if($client->credit_limit > 0)
                        <div class="flex items-center gap-2">
                            <span class="text-slate-700 font-medium">{{ config('regional.currency_symbol') }}{{ number_format($client->credit_limit, 2) }}</span>
                            @if($client->balance > $client->credit_limit)
                                <x-ui.badge variant="error" size="sm" :dot="false" class="whitespace-nowrap">EXCEDIDO</x-ui.badge>
                            @endif
                        </div>
                    @else
                        <x-ui.badge variant="slate" size="sm" :dot="false" class="whitespace-nowrap">Solo Contado</x-ui.badge>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="email" :visible="$visibleColumns">
                    <span class="text-zertix-secondary">{{ $client->email ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="phone" :visible="$visibleColumns">
                    {{ $client->phone ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="state" :visible="$visibleColumns">
                    {{ $client->provincia->name ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="city" :visible="$visibleColumns">
                    {{ $client->municipio->name ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="address" :visible="$visibleColumns">
                    {{ $client->address ?? '—' }}
                </x-data-table.cell>

                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <div class="flex items-center gap-1.5">
                        <x-ui.badge :variant="$client->is_active ? 'success' : 'slate'" size="sm" :dot="false">
                            {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                        </x-ui.badge>
                        @if($client->esMoroso())
                            <x-ui.badge variant="error" icon="heroicon-s-exclamation-triangle" size="sm">Moroso</x-ui.badge>
                        @endif
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $client->created_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if($client->trashed())
                            <x-ui.button
                                appearance="ghost" variant="success" size="sm" icon="heroicon-o-arrow-path"
                                wire:click="restore({{ $client->id }})"
                                aria-label="Restaurar cliente" title="Restaurar cliente" />

                            <x-ui.button
                                appearance="ghost" variant="error" size="sm" icon="heroicon-o-trash"
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $client->id }}')"
                                aria-label="Eliminar definitivamente" title="Eliminar definitivamente" />
                        @else
                            <x-ui.button
                                appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                                x-data @click="$dispatch('open-modal', 'view-client-{{ $client->id }}')"
                                aria-label="Ver detalles completos" title="Ver detalles completos" />

                            @unless($client->isConsumidorFinal())
                                <x-ui.action-menu>
                                    <x-ui.action-menu.item href="{{ route('clients.edit', $client) }}" icon="heroicon-o-pencil-square">
                                        Editar
                                    </x-ui.action-menu.item>
                                    <x-ui.action-menu.item
                                        x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $client->id }}')"
                                        icon="heroicon-o-trash" variant="danger">
                                        Eliminar
                                    </x-ui.action-menu.item>
                                </x-ui.action-menu>
                            @endunless
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                    <x-ui.empty-state variant="simple" title="Sin resultados"
                        description="No encontramos clientes que coincidan con los filtros aplicados." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('clients.partials.modals', ['clients' => $clients])
</div>
