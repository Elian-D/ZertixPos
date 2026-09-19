<div>

    <x-ui.page-header
        title="Historial de Facturación"
        description="Consulta el historial completo de facturas emitidas y su estado de pago."
        :count="$items->total()"
        countLabel="facturas"
    >
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
                    label="Tipo de Pago" filterKey="type"
                    :options="$payment_types"
                    placeholder="Todos" />

                <x-data-table.filter-select
                    label="Estado Documento" filterKey="status"
                    :options="$statuses"
                    placeholder="Todos" />

                <x-data-table.filter-select
                    label="Formato Original" filterKey="format_type"
                    :options="$formats"
                    placeholder="Todos los formatos" />

                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Emisión" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($items as $invoice)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="invoice_number" :visible="$visibleColumns">
                    <div class="flex items-center font-mono font-bold text-slate-900">
                        <x-heroicon-s-document-check class="w-4 h-4 mr-2 text-emerald-600" />
                        {{ $invoice->invoice_number }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="sale_id" :visible="$visibleColumns">
                    <span class="text-zertix-primary-600 font-medium italic">#{{ $invoice->sale->number ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="client_id" :visible="$visibleColumns">
                    <div class="font-medium text-slate-900">{{ $invoice->sale->client->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">ID: {{ $invoice->sale->client_id ?? '—' }}</div>
                </x-data-table.cell>

                <x-data-table.cell column="type" :visible="$visibleColumns">
                    @php
                        $pLabels = \App\Models\Sales\Sale::getPaymentTypes();
                        $pVariant = match($invoice->type) {
                            \App\Models\Sales\Sale::PAYMENT_CASH => 'info',
                            \App\Models\Sales\Sale::PAYMENT_CREDIT => 'warning',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$pVariant" size="sm" :dot="false">
                        {{ $pLabels[$invoice->type] ?? $invoice->type }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="format_type" :visible="$visibleColumns">
                    @php
                        $fIcons = \App\Models\Sales\Invoice::getFormatIcons();
                        $fLabels = \App\Models\Sales\Invoice::getFormats();
                    @endphp
                    <div class="flex items-center text-slate-600">
                        <x-dynamic-component :component="$fIcons[$invoice->format_type] ?? 'heroicon-s-question-mark-circle'" class="w-4 h-4 mr-1.5 text-slate-400" />
                        {{ $fLabels[$invoice->format_type] ?? $invoice->format_type }}
                    </div>
                </x-data-table.cell>

                {{-- grand_total (neto + impuesto), no total_amount (bruto) — mismo criterio Fase 5, REQ-5.12 --}}
                <x-data-table.cell column="total_amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right font-bold text-slate-900">
                    <span class="text-[10px] font-normal text-slate-400 mr-1">{{ config('regional.currency_symbol') }}</span>{{ number_format($invoice->sale->grand_total ?? 0, 2) }}
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @php
                        $sLabels = \App\Models\Sales\Invoice::getStatuses();
                        $sVariant = match($invoice->status) {
                            \App\Models\Sales\Invoice::STATUS_ACTIVE => 'success',
                            \App\Models\Sales\Invoice::STATUS_CANCELLED => 'error',
                            default => 'slate',
                        };
                    @endphp
                    <x-ui.badge :variant="$sVariant" size="sm" :dot="false">
                        {{ $sLabels[$invoice->status] ?? $invoice->status }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="due_date" :visible="$visibleColumns">
                    <span class="text-[11px] {{ $invoice->due_date && $invoice->due_date->isPast() && $invoice->status === 'active' ? 'text-red-500 font-bold' : 'text-slate-500' }}">
                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="generated_by" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $invoice->generated_by }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $invoice->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $invoice->created_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Categoría C (docs/analisis/politica-soft-deletes.md) — Invoice es
                             documento fiscal histórico, nunca se borra ni se archiva. --}}
                        <x-ui.action-menu>
                            <x-ui.action-menu.item href="{{ route('finance.invoices.show', $invoice) }}" icon="heroicon-o-eye">
                                Ver Factura
                            </x-ui.action-menu.item>
                            <x-ui.action-menu.item href="{{ route('finance.invoices.print', $invoice) }}" target="_blank" icon="heroicon-o-printer">
                                Imprimir Comprobante
                            </x-ui.action-menu.item>
                        </x-ui.action-menu>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-document-text" title="No se encontraron facturas emitidas"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>
</div>
