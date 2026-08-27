<div>

    <x-ui.page-header
        title="Kardex de Inventario"
        description="Consulta el historial de movimientos de inventario y registra ajustes manuales de stock."
        :count="$movements->total()"
        countLabel="movimientos"
    >
        <x-slot:actions>
            @can('create inventory adjustments')
                <x-ui.button variant="primary" iconLeft="heroicon-s-adjustments-vertical" x-data x-on:click="$dispatch('open-modal', 'create-adjustment')">
                    Ajuste de Stock
                </x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:secondary>
            <x-ui.button
                variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                wire:click="export">
                Exportar
            </x-ui.button>
        </x-slot:secondary>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$movements"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">

                <x-data-table.filter-select
                    label="Almacén" filterKey="warehouse_id"
                    :options="$warehouses->pluck('name', 'id')->all()"
                    placeholder="Todos los almacenes" />

                <x-data-table.filter-select
                    label="Tipo de Operación" filterKey="type"
                    :options="$types"
                    placeholder="Todas las operaciones" />

                <x-data-table.filter-group title="Rango de Fecha y Hora" collapsed>
                    <x-data-table.filter-date-range
                        label="Fecha del Movimiento" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($movements as $movement)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $movement->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $movement->created_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="product" :visible="$visibleColumns">
                    <span class="font-medium text-slate-900">{{ $movement->product->name ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="warehouse" :visible="$visibleColumns">
                    <span class="text-slate-600">{{ $movement->warehouse->name ?? 'N/A' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="type" :visible="$visibleColumns">
                    <x-ui.badge :variant="match($movement->type) {
                        'input' => 'success',
                        'output' => 'error',
                        'transfer' => 'info',
                        default => 'slate',
                    }" size="sm" :dot="false">
                        {{ $types[$movement->type] ?? $movement->type }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="toWarehouse" :visible="$visibleColumns">
                    @if($movement->type === 'transfer' && $movement->to_warehouse_id)
                        <div class="flex items-center text-blue-700 font-medium">
                            <x-heroicon-s-arrow-right-circle class="w-4 h-4 mr-1.5 opacity-70" />
                            {{ $movement->toWarehouse->name ?? 'N/A' }}
                        </div>
                    @elseif($movement->reference_type === 'App\Models\Inventory\InventoryMovement')
                        <span class="text-slate-400 italic text-xs">Origen de transferencia</span>
                    @else
                        <span class="text-slate-300 italic text-[10px]">No aplica</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="quantity" :visible="$visibleColumns">
                    <div class="flex items-center font-bold {{ $movement->quantity > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        @if($movement->quantity > 0)
                            <x-heroicon-s-arrow-trending-up class="w-4 h-4 mr-1" />
                        @else
                            <x-heroicon-s-arrow-trending-down class="w-4 h-4 mr-1" />
                        @endif
                        {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="balance" :visible="$visibleColumns" class="px-4 py-3.5 whitespace-nowrap">
                    <div class="flex flex-col border-l-2 border-slate-100 pl-2 text-xs">
                        <span class="text-slate-400">Previo: {{ number_format($movement->previous_stock, 2) }}</span>
                        <span class="font-bold text-slate-800">Final: {{ number_format($movement->current_stock, 2) }}</span>
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="user" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $movement->user->name ?? 'Sistema' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="reference" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-400 italic">
                        {{ $movement->reference_type ? class_basename($movement->reference_type).' #'.$movement->reference_id : 'Manual' }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="description" :visible="$visibleColumns" class="px-4 py-3.5 max-w-[150px] truncate">
                    <span class="text-xs text-slate-400 italic" title="{{ $movement->description }}">{{ $movement->description ?? 'Sin observaciones' }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button
                            appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                            x-data @click="$dispatch('open-modal', 'view-movement-{{ $movement->id }}')"
                            aria-label="Ver auditoría completa" title="Ver auditoría completa" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" title="No se encontraron movimientos"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    {{-- Modal para registrar ajustes manuales + modales de detalle por fila --}}
    @include('inventory.movements.partials.modals', ['items' => $movements])
</div>
