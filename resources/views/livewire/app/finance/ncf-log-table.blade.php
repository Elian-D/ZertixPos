<div>

    <x-ui.page-header
        title="Auditoría y Reportes NCF"
        description="Audita los comprobantes fiscales emitidos y genera los reportes exigidos por la DGII."
        :count="$items->total()"
        countLabel="registros"
    >
        <x-slot:secondary>
            <x-ui.button
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-document-arrow-down"
                wire:click="exportExcel">
                Excel
            </x-ui.button>

            <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'export-607-modal')"
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray">
                Generar 607 (TXT)
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
                    label="Tipo de Comprobante" filterKey="ncf_type_id"
                    :options="$ncf_types"
                    placeholder="Todos los tipos" />

                <x-data-table.filter-select
                    label="Estado" filterKey="status"
                    :options="$statuses"
                    placeholder="Todos los estados" />

                <x-data-table.filter-group title="Rangos de Búsqueda" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha de Emisión" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($items as $log)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="full_ncf" :visible="$visibleColumns" class="px-4 py-3.5 font-mono font-bold text-zertix-primary-700">
                    {{ $log->full_ncf }}
                </x-data-table.cell>

                <x-data-table.cell column="type_id" :visible="$visibleColumns">
                    <span class="text-slate-700 font-medium">{{ $log->type->code }}</span>
                    <span class="text-[10px] text-slate-400 block">{{ $log->type->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="sale_number" :visible="$visibleColumns">
                    <a href="{{ route('sales.index', $log->sale_id) }}" class="text-zertix-primary-600 hover:underline font-medium">
                        #{{ $log->sale->number ?? 'N/A' }}
                    </a>
                </x-data-table.cell>

                <x-data-table.cell column="customer" :visible="$visibleColumns">
                    <span class="text-slate-700 font-medium">{{ $log->sale->client->name ?? 'Consumidor Final' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="customer_rnc" :visible="$visibleColumns" class="px-4 py-3.5 font-mono text-slate-600">
                    {{ $log->sale->client->tax_id ?? 'N/A' }}
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @php
                        $statusVariants = [
                            \App\Models\Sales\Ncf\NcfLog::STATUS_USED   => 'info',
                            \App\Models\Sales\Ncf\NcfLog::STATUS_VOIDED => 'slate',
                        ];
                        $statusLabels = \App\Models\Sales\Ncf\NcfLog::getStatuses();
                    @endphp
                    <x-ui.badge :variant="$statusVariants[$log->status] ?? 'slate'" size="sm" :dot="false">
                        {{ strtoupper($statusLabels[$log->status] ?? $log->status) }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="cancellation_reason" :visible="$visibleColumns" class="px-4 py-3.5 text-red-500 italic">
                    {{ $log->cancellation_reason ?? '-' }}
                </x-data-table.cell>

                <x-data-table.cell column="user_id" :visible="$visibleColumns">
                    <span class="text-slate-500">{{ $log->user->name ?? 'Sist.' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <div class="font-medium text-slate-700">{{ $log->created_at->format('d/m/Y') }}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">{{ $log->created_at->format('h:i:s A') }}</div>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button
                            appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                            x-data @click="$dispatch('open-modal', 'view-log-{{ $log->id }}')"
                            aria-label="Ver detalle" title="Ver Detalle" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-document-magnifying-glass" title="No se encontraron registros de NCF"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('sales.ncf.logs.partials.modals', ['items' => $items])

    {{-- MODAL PARA PERIODO DGII --}}
    <x-modal name="export-607-modal" maxWidth="sm">
        <form action="{{ route('finance.ncf.logs.export.txt') }}" method="GET" class="p-6">
            <h3 class="text-lg font-bold text-gray-900">Exportar Reporte 607</h3>
            <p class="text-sm text-gray-600 mb-4">Seleccione el periodo fiscal para generar el archivo de texto.</p>

            <div>
                <x-ui.forms.input type="month" label="Periodo (Año/Mes)" name="periodo" id="periodo"
                              value="{{ now()->format('Y-m') }}"
                              required
                              hint="Genera el reporte 607 exigido por la DGII para el periodo seleccionado" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Descargar TXT</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
