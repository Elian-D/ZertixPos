@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $trash = $filters['trashed'] === 'only';
@endphp

<div>
    <x-ui.page-header title="Proveedores" description="A quién le compras y qué le debes." :count="$vendors->total()" countLabel="proveedores">
        @can('vendors.create')
            <x-slot:actions>
                <x-ui.button href="{{ route('purchases.vendors.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo proveedor
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    {{-- Papelera como pestaña del mismo índice (docs/analisis/politica-soft-deletes.md §6) --}}
    <div class="flex gap-1 mb-4">
        <x-ui.button size="sm" :variant="! $trash ? 'primary' : 'secondary'" :appearance="! $trash ? 'solid' : 'ghost'"
            wire:click="$set('filters.trashed', '')">Activos</x-ui.button>
        <x-ui.button size="sm" :variant="$trash ? 'error' : 'secondary'" :appearance="$trash ? 'solid' : 'ghost'"
            wire:click="$set('filters.trashed', 'only')">Papelera</x-ui.button>
    </div>

    <x-data-table.base-table
        :items="$vendors"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Condición" filterKey="payment_condition" :options="$conditions" placeholder="Todas" />
                    <x-data-table.filter-select label="Estado" filterKey="is_active" :options="$statuses" placeholder="Todos" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($vendors as $vendor)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="vendor-{{ $vendor->id }}">
                <x-data-table.cell column="business_name" :visible="$visibleColumns">
                    @if($trash)
                        <span class="font-semibold text-slate-700">{{ $vendor->display_name }}</span>
                    @else
                        <a href="{{ route('purchases.vendors.show', $vendor) }}" class="font-semibold text-zertix-primary-700 hover:underline">{{ $vendor->display_name }}</a>
                    @endif
                    @if($vendor->trade_name)
                        <span class="block text-xs text-slate-500">{{ $vendor->business_name }}</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="tax_id" :visible="$visibleColumns">
                    <span class="font-mono text-xs text-slate-700">{{ $vendor->tax_id ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="contact" :visible="$visibleColumns">
                    <span class="block text-xs text-slate-700">{{ $vendor->contact_name ?? '—' }}</span>
                    @if($vendor->phone)<span class="block text-xs text-slate-500">{{ $vendor->phone }}</span>@endif
                </x-data-table.cell>

                <x-data-table.cell column="condition" :visible="$visibleColumns">
                    <x-ui.badge :variant="$vendor->payment_condition === 'credit' ? 'info' : 'slate'" size="sm" :dot="false">
                        {{ $vendor->condition_label }}{{ $vendor->payment_condition === 'credit' ? ' · '.$vendor->credit_days.' días' : '' }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="pending" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-semibold tabular-nums', 'text-state-warning' => $vendor->pending_balance > 0, 'text-slate-400' => ! $vendor->pending_balance])>
                        {{ $money($vendor->pending_balance ?? 0) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="rating" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$vendor->rating_variant" size="sm" :dot="false">{{ $vendor->rating_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="is_active" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$vendor->is_active ? 'success' : 'slate'" size="sm" :dot="false">{{ $vendor->is_active ? 'Activo' : 'Inactivo' }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $vendor->created_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        @if($trash)
                            @can('vendors.restore')
                                <x-ui.action-menu.item wire:click="restore({{ $vendor->id }})" icon="heroicon-o-arrow-uturn-left">Restaurar</x-ui.action-menu.item>
                            @endcan
                            @can('vendors.delete')
                                <x-ui.action-menu.item x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $vendor->id }}')" icon="heroicon-o-trash" variant="danger">
                                    Eliminar definitivo
                                </x-ui.action-menu.item>
                            @endcan
                        @else
                            <x-ui.action-menu.item href="{{ route('purchases.vendors.show', $vendor) }}" icon="heroicon-o-eye">Ver</x-ui.action-menu.item>
                            @can('vendors.edit')
                                <x-ui.action-menu.item href="{{ route('purchases.vendors.edit', $vendor) }}" icon="heroicon-o-pencil-square">Editar</x-ui.action-menu.item>
                            @endcan
                            @can('vendors.delete')
                                <x-ui.action-menu.item x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $vendor->id }}')" icon="heroicon-o-trash" variant="danger">
                                    Eliminar
                                </x-ui.action-menu.item>
                            @endcan
                        @endif
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-building-storefront"
                        :title="$trash ? 'La papelera está vacía' : 'No hay proveedores'"
                        :description="$trash ? 'Aquí quedan los proveedores eliminados, con su historial.' : 'Registra a quién le compras para darle seguimiento a lo que le debes.'" />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    @can('vendors.delete')
        @foreach($vendors as $vendor)
            @if($trash)
                <x-ui.confirm-deletion-modal :id="$vendor->id" title="¿Eliminar permanentemente?" :itemName="$vendor->display_name" type="el proveedor"
                    :wireConfirm="'forceDelete('.$vendor->id.')'"
                    :description="'Estás a punto de borrar definitivamente a <strong>'.e($vendor->display_name).'</strong>. Solo se puede si no tiene cuentas por pagar ni gastos.'" />
            @else
                <x-ui.confirm-deletion-modal :id="$vendor->id" title="¿Eliminar proveedor?" :itemName="$vendor->display_name" type="el proveedor"
                    :route="route('purchases.vendors.destroy', $vendor)" />
            @endif
        @endforeach
    @endcan
</div>
