<div>

    <x-ui.page-header
        title="Recibos de Cobro"
        description="Registra y consulta los cobros realizados por los clientes sobre facturas y cuentas por cobrar."
        :count="$items->total()"
        countLabel="cobros"
    >
        <x-slot:actions>
            @can('create payments')
                <x-ui.button href="{{ route('finance.collections.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo Cobro
                </x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:secondary>
            <x-ui.button
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                wire:click="export">
                Exportar (Excel)
            </x-ui.button>
        </x-slot:secondary>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$items"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Cliente" filterKey="client_id"
                    :options="$clients->pluck('name', 'id')->all()"
                    placeholder="Todos los clientes" />

                <x-data-table.filter-select
                    label="Método de Pago" filterKey="tipo_pago_id"
                    :options="$paymentMethods->pluck('nombre', 'id')->all()"
                    placeholder="Todos los métodos" />

                <x-data-table.filter-select
                    label="Estado" filterKey="status"
                    :options="$statuses"
                    placeholder="Todos" />

                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Cobro" fromKey="from_date" toKey="to_date" />

                    <x-data-table.filter-range
                        label="Monto Cobrado" fromKey="min_amount" toKey="max_amount"
                        prefix="{{ config('regional.currency_symbol') }}" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($items as $payment)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="payment_date" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $payment->payment_date->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $payment->created_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="receipt_number" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-zertix-primary-700">{{ $payment->receipt_number }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="client" :visible="$visibleColumns">
                    <div class="font-medium text-slate-900">{{ $payment->client->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">{{ $payment->client->tax_id ?? 'Sin RNC/Cédula' }}</div>
                </x-data-table.cell>

                <x-data-table.cell column="receivable" :visible="$visibleColumns">
                    @if($payment->receivable)
                        <span class="text-slate-700 font-medium">{{ $payment->receivable->document_number }}</span>
                    @else
                        <span class="text-slate-400 italic">Anticipo / General</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="tipo_pago" :visible="$visibleColumns">
                    @php
                        $tpHex = \App\Models\Configuration\TipoPago::getBadgeHexColors()[$payment->tipoPago->slug ?? null]
                            ?? \App\Models\Configuration\TipoPago::getDefaultBadgeHex();
                        $tpIcon = \App\Models\Configuration\TipoPago::getBadgeIcons()[$payment->tipoPago->slug ?? null]
                            ?? \App\Models\Configuration\TipoPago::getDefaultBadgeIcon();
                    @endphp
                    <x-ui.badge :hex="$tpHex" :icon="$tpIcon" size="sm">
                        {{ $payment->tipoPago->nombre ?? 'N/A' }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="reference" :visible="$visibleColumns" class="px-4 py-3.5 max-w-[150px] truncate">
                    <span class="text-slate-500" title="{{ $payment->reference }}">{{ $payment->reference ?? '---' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right font-bold text-slate-900">
                    {{ number_format($payment->amount, 2) }}
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @php
                        $statusVariant = match($payment->status) {
                            \App\Models\Accounting\ClientCollection::STATUS_ACTIVE => 'success',
                            \App\Models\Accounting\ClientCollection::STATUS_CANCELLED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$statusVariant" size="sm" :dot="false">
                        {{ $payment->status_label }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_by" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $payment->creator->name ?? 'Sistema' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-400">{{ $payment->created_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Categoría C (docs/analisis/politica-soft-deletes.md) — un Cobro es
                             bitácora de dinero recibido, nunca se borra ni se archiva. --}}
                        <x-ui.action-menu>
                            <x-ui.action-menu.item
                                x-data @click="$dispatch('open-modal', 'view-payment-{{ $payment->id }}')"
                                icon="heroicon-o-eye">
                                Ver Recibo
                            </x-ui.action-menu.item>

                            <x-ui.action-menu.item
                                href="{{ route('finance.collections.print', $payment->id) }}" target="_blank"
                                icon="heroicon-o-printer">
                                Imprimir Recibo
                            </x-ui.action-menu.item>

                            @can('cancel payments')
                                @if($payment->status === \App\Models\Accounting\ClientCollection::STATUS_ACTIVE)
                                    <x-ui.action-menu.item
                                        x-data @click="$dispatch('open-modal', 'confirm-cancel-payment-{{ $payment->id }}')"
                                        icon="heroicon-o-x-circle" variant="danger">
                                        Anular Cobro
                                    </x-ui.action-menu.item>
                                @endif
                            @endcan
                        </x-ui.action-menu>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-document-text" title="No se encontraron registros de cobros"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('finance.collections.partials.modals', ['items' => $items])
</div>
