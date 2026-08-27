<div>

    <x-ui.page-header
        title="Gestión de Ventas"
        description="Consulta y administra el pipeline de ventas, desde la creación hasta el cobro."
        :count="$sales->total()"
        countLabel="ventas"
    >
        <x-slot:actions>
            @can('create sales')
                <x-ui.button href="{{ route('sales.create') }}" variant="primary" iconLeft="heroicon-s-plus-circle">
                    Nueva Venta
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
        :items="$sales"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select
                        label="Cliente" filterKey="client_id"
                        :options="$clients->pluck('name', 'id')->all()"
                        placeholder="Todos los clientes" />

                    <x-data-table.filter-select
                        label="Tipo de Pago" filterKey="payment_type"
                        :options="$payment_types"
                        placeholder="Todos" />

                    <x-data-table.filter-select
                        label="Estado" filterKey="status"
                        :options="$statuses"
                        placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Ubicación" collapsed>
                    <x-data-table.filter-select
                        label="Almacén" filterKey="warehouse_id"
                        :options="$warehouses->pluck('name', 'id')->all()"
                        placeholder="Todos los almacenes" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Punto de Venta" collapsed>
                    <x-data-table.filter-select
                        label="Sesión POS" filterKey="pos_session_id"
                        :options="$pos_sessions"
                        placeholder="Todas las sesiones" />

                    <x-data-table.filter-select
                        label="Terminal POS" filterKey="pos_terminal_id"
                        :options="$pos_terminals"
                        placeholder="Todas las terminales" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Métodos de Pago" collapsed>
                    <x-data-table.filter-select
                        label="Método (Efectivo/Transf.)" filterKey="tipo_pago_id"
                        :options="$tipo_pagos->pluck('nombre', 'id')->all()"
                        placeholder="Todos los métodos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Venta" fromKey="from_date" toKey="to_date" />

                    <x-data-table.filter-range
                        label="Monto Total" fromKey="min_amount" toKey="max_amount" prefix="{{ config('regional.currency_symbol') }}" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($sales as $sale)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="sale_date" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $sale->sale_date->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $sale->sale_date->format('h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-zertix-primary-700">{{ $sale->number }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="client_id" :visible="$visibleColumns">
                    <div class="font-medium text-slate-800">{{ $sale->client->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">{{ $sale->client->tax_id ?? 'Consumidor Final' }}</div>
                </x-data-table.cell>

                <x-data-table.cell column="warehouse_id" :visible="$visibleColumns">
                    <div class="flex items-center text-slate-600">
                        <x-heroicon-s-building-storefront class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                        {{ $sale->warehouse->name ?? 'N/A' }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="pos_terminal_id" :visible="$visibleColumns">
                    @if($sale->pos_terminal_id)
                        <div class="flex items-center">
                            <div class="p-1 bg-blue-50 rounded mr-2">
                                <x-heroicon-s-computer-desktop class="w-3.5 h-3.5 text-blue-500" />
                            </div>
                            <span class="font-medium text-slate-700">{{ $sale->posTerminal->name }}</span>
                        </div>
                    @else
                        <span class="text-slate-400 italic text-xs">Ventanilla/Admin</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="pos_session_id" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @if($sale->pos_session_id)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-mono font-bold ring-1 ring-inset ring-slate-200">
                            #{{ $sale->pos_session_id }}
                        </span>
                    @else
                        <span class="text-slate-300">-</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="payment_type" :visible="$visibleColumns">
                    @php
                        $pIcons  = \App\Models\Sales\Sale::getPaymentTypeIcons();
                        $pLabels = \App\Models\Sales\Sale::getPaymentTypes();
                        $currentIcon  = $pIcons[$sale->payment_type] ?? 'heroicon-s-question-mark-circle';
                        $currentVariant = match($sale->payment_type) {
                            \App\Models\Sales\Sale::PAYMENT_CASH => 'info',
                            \App\Models\Sales\Sale::PAYMENT_CREDIT => 'warning',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$currentVariant" :icon="$currentIcon" size="sm">
                        {{ $pLabels[$sale->payment_type] ?? $sale->payment_type }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="tipo_pago_id" :visible="$visibleColumns">
                    @php
                        // No usamos $sale->tipoPago->nombre: ese campo es sales.tipo_pago_id,
                        // el "principal" heredado de antes de existir pago dividido. Una venta
                        // mixta (varias filas en sale_payments) mostraba solo ese método suelto
                        // en vez de "Mixto" — misma lógica que ya usa el reporte de turno POS.
                        $isCredit = $sale->payment_type === \App\Models\Sales\Sale::PAYMENT_CREDIT;
                        $isMixed = !$isCredit && $sale->payments->count() > 1;
                        $singlePayment = (!$isCredit && !$isMixed) ? $sale->payments->first()?->tipoPago : null;

                        $paymentBadgeKey = $isCredit
                            ? \App\Models\Configuration\TipoPago::CREDITO
                            : ($isMixed
                                ? \App\Models\Configuration\TipoPago::MIXTO
                                : ($singlePayment->slug ?? null));

                        $paymentLabel = $isCredit ? 'Crédito' : ($isMixed ? 'Mixto' : ($singlePayment->nombre ?? 'N/A'));

                        $pmHex   = \App\Models\Configuration\TipoPago::getBadgeHexColors();
                        $pmIcons = \App\Models\Configuration\TipoPago::getBadgeIcons();
                        $paymentBadgeHex  = $pmHex[$paymentBadgeKey] ?? \App\Models\Configuration\TipoPago::getDefaultBadgeHex();
                        $paymentBadgeIcon = $pmIcons[$paymentBadgeKey] ?? \App\Models\Configuration\TipoPago::getDefaultBadgeIcon();
                    @endphp
                    <x-ui.badge :hex="$paymentBadgeHex" :icon="$paymentBadgeIcon" size="sm">
                        {{ $paymentLabel }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- grand_total (neto + impuesto), no total_amount (bruto) — mismo criterio de Fase 5, REQ-5.12 --}}
                <x-data-table.cell column="total_amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="text-[10px] font-normal text-slate-400 mr-1">{{ config('regional.currency_symbol') }}</span><span class="font-bold text-slate-900">{{ number_format($sale->grand_total, 2) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @php
                        $sLabels = \App\Models\Sales\Sale::getStatuses();
                        $sVariant = match($sale->status) {
                            \App\Models\Sales\Sale::STATUS_COMPLETED => 'success',
                            \App\Models\Sales\Sale::STATUS_CANCELED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$sVariant" size="sm" :dot="false">
                        {{ $sLabels[$sale->status] ?? $sale->status }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="user_id" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $sale->user->name ?? 'Sistema' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="notes" :visible="$visibleColumns" class="px-4 py-3.5 max-w-[150px] truncate">
                    <span class="text-xs text-slate-400 italic" title="{{ $sale->notes }}">{{ $sale->notes ?? 'Sin observaciones' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-400">{{ $sale->created_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-400">{{ $sale->updated_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.action-menu>
                            <x-ui.action-menu.item
                                x-data @click="$dispatch('open-modal', 'view-sale-{{ $sale->id }}')"
                                icon="heroicon-o-eye">
                                Ver Detalle
                            </x-ui.action-menu.item>

                            <x-ui.action-menu.item
                                href="{{ route('sales.print-invoice', $sale) }}" target="_blank"
                                icon="heroicon-o-printer">
                                Imprimir Comprobante
                            </x-ui.action-menu.item>

                            @can('cancel sales')
                                @if($sale->status === \App\Models\Sales\Sale::STATUS_COMPLETED)
                                    <x-ui.action-menu.item
                                        x-data @click="$dispatch('open-modal', 'confirm-cancel-sale-{{ $sale->id }}')"
                                        icon="heroicon-o-x-circle" variant="danger">
                                        Anular Venta
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
                    <x-ui.empty-state variant="simple" icon="heroicon-o-shopping-cart" title="No se encontraron registros de ventas"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('sales.partials.modals', ['items' => $sales])
</div>
