<div>

    <x-ui.page-header
        title="Configuración de Secuencias NCF"
        description="Administra los lotes de secuencias de comprobantes fiscales autorizados por la DGII."
        :count="$items->total()"
        countLabel="lotes"
    >
        <x-slot:actions>
            @can('manage ncf sequences')
                <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'create-ncf-sequence')"
                    variant="primary" iconLeft="heroicon-s-plus-circle">
                    Nuevo Lote NCF
                </x-ui.button>
            @endcan
        </x-slot:actions>
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
                    label="Tipo de NCF" filterKey="ncf_type_id"
                    :options="$ncf_types"
                    placeholder="Todos los tipos" />

                <x-data-table.filter-select
                    label="Estado" filterKey="status"
                    :options="$statuses"
                    placeholder="Todos los estados" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($items as $sequence)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="type_id" :visible="$visibleColumns">
                    <div class="font-bold text-zertix-primary-700">{{ $sequence->type->code }}</div>
                    <div class="text-[10px] text-slate-500 uppercase leading-tight">{{ $sequence->type->name }}</div>
                </x-data-table.cell>

                <x-data-table.cell column="series" :visible="$visibleColumns" class="px-4 py-3.5 text-center font-bold text-slate-700">
                    {{ $sequence->series }}
                </x-data-table.cell>

                <x-data-table.cell column="range" :visible="$visibleColumns" class="px-4 py-3.5 text-xs font-mono text-slate-600">
                    <span class="bg-slate-100 px-1.5 py-0.5 rounded">{{ str_pad($sequence->from, $sequence->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}</span>
                    <span class="mx-1 text-slate-300">→</span>
                    <span class="bg-slate-100 px-1.5 py-0.5 rounded">{{ str_pad($sequence->to, $sequence->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="current" :visible="$visibleColumns" class="px-4 py-3.5 text-sm font-mono font-bold">
                    @if($sequence->current >= $sequence->from)
                        <span class="text-zertix-primary-600">{{ $sequence->series }}{{ $sequence->type->code }}{{ str_pad($sequence->current, 8, '0', STR_PAD_LEFT) }}</span>
                    @else
                        <span class="text-slate-300 italic text-[10px]">Sin uso</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="available" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @php $available = $sequence->to - $sequence->current; @endphp
                    <span class="font-bold {{ $sequence->isLow() ? 'text-red-600 animate-pulse' : 'text-slate-700' }}">
                        {{ number_format($available) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="usage_percent" :visible="$visibleColumns">
                    @php
                        $total = ($sequence->to - $sequence->from + 1);
                        $used = ($sequence->current >= $sequence->from) ? ($sequence->current - $sequence->from + 1) : 0;
                        $percent = ($total > 0) ? ($used / $total) * 100 : 0;
                        $barColor = $percent > 90 ? 'bg-red-500' : ($percent > 70 ? 'bg-orange-400' : 'bg-green-500');
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-16 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                            <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500"
                                style="width: {{ min($percent, 100) }}%"></div>
                        </div>
                        <span class="text-[10px] font-medium text-slate-500">{{ round($percent, 1) }}%</span>
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="expiry_date" :visible="$visibleColumns">
                    <div class="text-xs {{ $sequence->expiry_date->isPast() ? 'text-red-600 font-bold' : 'text-slate-600' }}">
                        {{ $sequence->expiry_date->format('d/m/Y') }}
                    </div>
                    <div class="text-[9px] {{ $sequence->expiry_date->isPast() ? 'text-red-400' : 'text-slate-400' }} uppercase font-medium">
                        @if($sequence->expiry_date->isPast())
                            Vencido hace {{ $sequence->expiry_date->diffForHumans(['parts' => 2, 'join' => ', ']) }}
                        @else
                            Vence en {{ $sequence->expiry_date->diffForHumans() }}
                        @endif
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="alert_threshold" :visible="$visibleColumns" class="px-4 py-3.5 text-xs text-center text-slate-400 font-medium">
                    {{ number_format($sequence->alert_threshold) }}
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="match($sequence->calculated_status) {
                            \App\Models\Sales\Ncf\NcfSequence::STATUS_ACTIVE => 'success',
                            \App\Models\Sales\Ncf\NcfSequence::STATUS_EXHAUSTED => 'warning',
                            \App\Models\Sales\Ncf\NcfSequence::STATUS_EXPIRED => 'error',
                            default => 'slate',
                        }" size="sm" class="shadow-sm">
                        {{ $sequence->status_label }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-[10px] text-slate-400">{{ $sequence->created_at->format('d/m/Y h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button
                            appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                            x-data @click="$dispatch('open-modal', 'view-sequence-{{ $sequence->id }}')"
                            aria-label="Ver detalle" title="Ver Detalle y Estadísticas" />

                        @can('manage ncf sequences')
                            <x-ui.action-menu>
                                <x-ui.action-menu.item
                                    x-data @click="$dispatch('open-modal', 'extend-sequence-{{ $sequence->id }}')"
                                    icon="heroicon-o-arrow-trending-up">
                                    Ampliar Rango
                                </x-ui.action-menu.item>

                                {{-- Eliminar solo si no se ha usado ni un solo número (Lote virgen) —
                                     mismo guard que NcfSequenceService::delete(). --}}
                                @if($sequence->current < $sequence->from)
                                    <x-ui.action-menu.item
                                        x-data @click="$dispatch('open-modal', 'confirm-sequence-deletion-{{ $sequence->id }}')"
                                        icon="heroicon-o-trash" variant="danger">
                                        Eliminar Lote
                                    </x-ui.action-menu.item>
                                @endif
                            </x-ui.action-menu>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-document-duplicate" title="No hay secuencias configuradas"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('sales.ncf.sequences.partials.modals', ['items' => $items])
</div>
