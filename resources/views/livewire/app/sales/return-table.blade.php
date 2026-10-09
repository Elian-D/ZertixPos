@php $currency = config('regional.currency_symbol'); @endphp

<div>
    <x-ui.page-header
        title="Devoluciones"
        description="Historial de devoluciones y cambios. Se registran desde el listado de Ventas (acción Devolver)."
        :count="$returns->total()"
        countLabel="devoluciones"
    />

    <x-data-table.base-table
        :items="$returns"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Método" filterKey="refund_method" :options="$refund_methods" placeholder="Todos" />
                    <x-data-table.filter-select label="Motivo" filterKey="reason" :options="$reasons" placeholder="Todos" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                    <x-data-table.filter-select label="Usuario" filterKey="user_id" :options="$users" placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha de devolución" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($returns as $return)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="return-{{ $return->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('sales.returns.show', $return) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $return->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="sale_id" :visible="$visibleColumns">
                    <span class="font-mono text-slate-700">{{ $return->sale->number ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="client" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $return->sale->client->name ?? 'Consumidor Final' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="user_id" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $return->user->name ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="reason" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $return->reason_label }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="refund_method" :visible="$visibleColumns">
                    @php
                        $methodVariant = match($return->refund_method) {
                            \App\Models\Sales\Returns\SaleReturn::METHOD_CASH => 'info',
                            \App\Models\Sales\Returns\SaleReturn::METHOD_EXCHANGE => 'primary',
                            default => 'warning',
                        };
                    @endphp
                    <x-ui.badge :variant="$methodVariant" size="sm" :dot="false">{{ $return->refund_method_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="refund_value" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="text-[10px] font-normal text-slate-400 mr-1">{{ $currency }}</span><span class="font-bold text-slate-900">{{ number_format($return->refund_value, 2) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$return->isVoided() ? 'error' : 'success'" size="sm" :dot="false">
                        {{ \App\Models\Sales\Returns\SaleReturn::getStatuses()[$return->status] ?? $return->status }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $return->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $return->created_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        <x-ui.action-menu.item href="{{ route('sales.returns.show', $return) }}" icon="heroicon-o-eye">
                            Ver
                        </x-ui.action-menu.item>
                        <x-ui.action-menu.item href="{{ route('sales.returns.print', $return) }}" target="_blank" icon="heroicon-o-printer">
                            Ticket
                        </x-ui.action-menu.item>
                        @can('returns.void')
                            @unless($return->isVoided())
                                <x-ui.action-menu.item
                                    x-data @click="$dispatch('open-modal', 'confirm-deletion-return-{{ $return->id }}')"
                                    icon="heroicon-o-x-circle" variant="danger">
                                    Anular
                                </x-ui.action-menu.item>
                            @endunless
                        @endcan
                    </x-ui.action-menu>

                    @can('returns.void')
                        @unless($return->isVoided())
                            <x-ui.confirm-deletion-modal
                                :id="'return-'.$return->id"
                                title="¿Anular devolución?"
                                :itemName="$return->number"
                                type="la devolución"
                                :wireConfirm="'void('.$return->id.')'"
                                description="Se revierte el inventario (y su merma, si la tiene) y, si aplica, la deuda del cliente. El efectivo entregado se corrige a mano." />
                        @endunless
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-arrow-uturn-left" title="No hay devoluciones registradas"
                        description="Se registran desde el listado de Ventas, en la acción Devolver de una venta." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
