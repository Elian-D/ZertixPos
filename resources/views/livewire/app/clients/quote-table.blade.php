<div>

    <x-ui.page-header
        title="Motor de Cotizaciones"
        description="Crea y da seguimiento a las cotizaciones enviadas a clientes antes de convertirlas en ventas."
        :count="$quotes->total()"
        countLabel="cotizaciones"
    >
        <x-slot:actions>
            @can('quotes.create')
                <x-ui.button href="{{ route('clients.quotes.create') }}" variant="primary" iconLeft="heroicon-s-plus-circle">
                    Nueva Cotización
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$quotes"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select
                        label="Cliente" filterKey="customer_id"
                        :options="$customers->pluck('name', 'id')->all()"
                        placeholder="Todos los clientes" />

                    <x-data-table.filter-select
                        label="Estado" filterKey="status"
                        :options="$statuses"
                        placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Origen y Usuario" collapsed>
                    <x-data-table.filter-select
                        label="Origen" filterKey="origin"
                        :options="$origins"
                        placeholder="Todos" />

                    <x-data-table.filter-select
                        label="Vendedor / Creador" filterKey="user_id"
                        :options="$users->pluck('name', 'id')->all()"
                        placeholder="Todos los usuarios" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Rangos de Fecha" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Creación" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($quotes as $quote)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-zertix-primary">#{{ $quote->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $quote->created_at->format('d/m/Y H:i') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="customer_id" :visible="$visibleColumns">
                    <div class="font-medium text-slate-800">{{ $quote->customer->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">
                        {{ $quote->customer->tax_id ?? 'Sin RNC/Cédula' }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="user_id" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-500">{{ $quote->user->name ?? 'Sistema' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="origin" :visible="$visibleColumns">
                    <div class="flex items-center text-[11px] uppercase font-semibold text-slate-600">
                        @if($quote->origin === \App\Models\Sales\Quotes\Quote::ORIGIN_POS)
                            <x-heroicon-s-computer-desktop class="w-3.5 h-3.5 mr-1.5 text-blue-400" />
                        @else
                            <x-heroicon-s-building-office class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                        @endif
                        {{ $quote->origin }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns">
                    @php
                        $sLabels = \App\Models\Sales\Quotes\Quote::getStatuses();
                        $sVariant = match($quote->status) {
                            \App\Models\Sales\Quotes\Quote::STATUS_DRAFT => 'slate',
                            \App\Models\Sales\Quotes\Quote::STATUS_APPROVED => 'info',
                            \App\Models\Sales\Quotes\Quote::STATUS_CONVERTED => 'success',
                            \App\Models\Sales\Quotes\Quote::STATUS_EXPIRED => 'warning',
                            \App\Models\Sales\Quotes\Quote::STATUS_CANCELLED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$sVariant" size="sm" :dot="false">
                        {{ $sLabels[$quote->status] ?? $quote->status }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- grand_total (neto + impuesto), no total — mismo criterio de Fase 5, REQ-5.12 --}}
                <x-data-table.cell column="total" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span class="text-[10px] font-normal text-slate-400 mr-1">{{ config('regional.currency_symbol') }}</span><span class="font-bold text-slate-800">{{ number_format($quote->grand_total, 2) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="expires_at" :visible="$visibleColumns">
                    @php $isExpired = $quote->expires_at->isPast() && $quote->status !== 'converted'; @endphp
                    <span class="text-xs {{ $isExpired ? 'text-red-500 font-bold' : 'text-slate-500' }}">
                        {{ $quote->expires_at->format('d/m/Y') }}
                    </span>
                    @if($isExpired)
                        <span class="block text-[9px] uppercase font-black text-red-400 italic">Expirada</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="sale_id" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @if($quote->sale && $quote->sale->invoice)
                        <a href="{{ route('finance.invoices.show', $quote->sale->invoice->id) }}" class="whitespace-nowrap inline-flex items-center px-2 py-1 bg-emerald-50 text-emerald-700 rounded border border-emerald-100 text-[10px] font-bold hover:bg-emerald-100 transition">
                            {{ $quote->sale->number }}
                        </a>
                    @else
                        <span class="text-slate-400 text-xs">N/A</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="pos_terminal_id" :visible="$visibleColumns">
                    <span class="text-[10px] text-slate-400">{{ $quote->terminal->name ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="notes" :visible="$visibleColumns" class="px-4 py-3.5 max-w-xs truncate">
                    <span class="text-[10px] text-slate-400">{{ $quote->notes ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-[10px] text-slate-400">{{ $quote->updated_at->diffForHumans() }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button
                            appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                            href="{{ route('clients.quotes.show', $quote) }}"
                            aria-label="Ver detalle" title="Ver Detalle" />

                        @if(!$quote->sale_id && !$quote->expires_at->isPast())
                            @php
                                $hasRowActions = $quote->status === \App\Models\Sales\Quotes\Quote::STATUS_DRAFT
                                    || $quote->status === \App\Models\Sales\Quotes\Quote::STATUS_APPROVED
                                    || $quote->status !== \App\Models\Sales\Quotes\Quote::STATUS_CANCELLED;
                            @endphp
                            @if($hasRowActions)
                                <x-ui.action-menu>
                                    @if($quote->status === \App\Models\Sales\Quotes\Quote::STATUS_DRAFT)
                                        <x-ui.action-menu.item
                                            x-data @click="$dispatch('open-modal', 'confirm-approve-quote-{{ $quote->id }}')"
                                            icon="heroicon-o-check-circle">
                                            Aprobar
                                        </x-ui.action-menu.item>
                                    @endif

                                    @if($quote->status === \App\Models\Sales\Quotes\Quote::STATUS_APPROVED)
                                        <x-ui.action-menu.item
                                            x-data @click="$dispatch('open-modal', 'confirm-convert-quote-{{ $quote->id }}')"
                                            icon="heroicon-o-shopping-cart">
                                            Convertir a Venta
                                        </x-ui.action-menu.item>
                                    @endif

                                    @if($quote->status === \App\Models\Sales\Quotes\Quote::STATUS_DRAFT && !$quote->expires_at->isPast())
                                        <x-ui.action-menu.item href="{{ route('clients.quotes.edit', $quote) }}" icon="heroicon-o-pencil-square">
                                            Editar
                                        </x-ui.action-menu.item>
                                    @endif

                                    @if($quote->status !== \App\Models\Sales\Quotes\Quote::STATUS_CANCELLED)
                                        <x-ui.action-menu.item
                                            x-data @click="$dispatch('open-modal', 'confirm-cancel-quote-{{ $quote->id }}')"
                                            icon="heroicon-o-x-circle" variant="danger">
                                            Cancelar
                                        </x-ui.action-menu.item>
                                    @endif
                                </x-ui.action-menu>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" title="No se encontraron cotizaciones"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('sales.quotes.partials.actions-modals', ['items' => $quotes])
</div>
