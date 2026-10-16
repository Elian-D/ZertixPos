@php $currency = config('regional.currency_symbol'); @endphp

<div>
    <x-ui.page-header
        title="Tomas físicas"
        description="Cuenta lo que hay de verdad en un almacén; al aplicar, el sistema se ajusta a lo contado."
        :count="$counts->total()"
        countLabel="tomas"
    >
        @can('inventory_counts.create')
            <x-slot:actions>
                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'create-count')">
                    Nueva toma física
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$counts"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Almacén" filterKey="warehouse_id" :options="$warehouses" placeholder="Todos" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha de creación" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($counts as $count)
            <tr class="hover:bg-slate-50 transition-colors duration-150" wire:key="count-{{ $count->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    @can('inventory_counts.view')
                        <a href="{{ route('inventory.counts.show', $count) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $count->number }}</a>
                    @elsecan('inventory_counts.count')
                        <a href="{{ route('inventory.counts.count', $count) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $count->number }}</a>
                    @endcan
                    @if($count->blind)
                        <span class="block text-[10px] text-slate-400">Conteo ciego</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="warehouse" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $count->warehouse->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="scope" :visible="$visibleColumns">
                    <span class="text-xs text-slate-600">{{ $count->scope_label }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="progress" :visible="$visibleColumns">
                    <span class="text-xs font-semibold text-slate-700 tabular-nums">{{ $count->counted_items_count }} / {{ $count->items_count }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="difference_value" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    {{-- No es dinero de caja: es el valor al costo de lo que faltó o sobró --}}
                    @if($count->isApplied())
                        <span title="Valor al costo del inventario ajustado — no mueve dinero de caja ni banco" @class(['font-bold tabular-nums', 'text-state-error' => $count->difference_value < 0, 'text-emerald-600' => $count->difference_value > 0, 'text-slate-500' => $count->difference_value == 0])>
                            {{ $count->difference_value > 0 ? '+' : '' }}{{ $currency }}{{ number_format($count->difference_value, 2) }}
                        </span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$count->status_variant" size="sm" :dot="false">{{ $count->status_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="creator" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $count->creator->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="block text-xs font-medium text-slate-700">{{ $count->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400">{{ $count->created_at->format('h:i A') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.action-menu>
                        @can('inventory_counts.view')
                            <x-ui.action-menu.item href="{{ route('inventory.counts.show', $count) }}" icon="heroicon-o-eye">
                                Ver
                            </x-ui.action-menu.item>
                        @endcan
                        @if($count->isDraft())
                            @can('inventory_counts.count')
                                <x-ui.action-menu.item href="{{ route('inventory.counts.count', $count) }}" icon="heroicon-o-pencil-square">
                                    Contar
                                </x-ui.action-menu.item>
                            @endcan
                        @endif
                        @can('inventory_counts.view')
                            <x-ui.action-menu.item href="{{ route('inventory.counts.pdf', $count) }}" target="_blank" icon="heroicon-o-printer">
                                PDF
                            </x-ui.action-menu.item>
                        @endcan
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-clipboard-document-check" title="No hay tomas físicas"
                        description="Crea una para contar lo que hay en un almacén y ajustar el sistema a lo contado." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    {{-- Modal: nueva toma física (4 campos → modal, CLAUDE.md "Show views, forms and when a modal is enough") --}}
    @can('inventory_counts.create')
        @php $countErrors = $errors->hasAny(['warehouse_id', 'scope', 'category_id', 'blind', 'notes']); @endphp
        <x-modal name="create-count" maxWidth="lg" :show="$countErrors">
            <form method="POST" action="{{ route('inventory.counts.store') }}" class="p-6"
                x-data="{ scope: @js(old('scope', 'all')) }">
                @csrf

                <h3 class="text-lg font-semibold text-gray-900">Nueva toma física</h3>
                <p class="mt-1 text-sm text-gray-500">Se guarda la existencia actual de cada producto. Las ventas siguen normales.</p>

                <div class="mt-6 flex flex-col gap-5">
                    <x-ui.forms.select label="Almacén" name="warehouse_id" placeholder="Seleccione el almacén..." required
                        hint="Una toma abierta a la vez por almacén."
                        :error="$errors->first('warehouse_id')">
                        @foreach($warehouses as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('warehouse_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <div>
                        <span class="block text-xs font-semibold text-slate-600 mb-1.5">Alcance <span class="text-state-error">*</span></span>
                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            <x-ui.forms.radio label="Todo el almacén" name="scope" value="all" x-model="scope" />
                            <x-ui.forms.radio label="Una categoría" name="scope" value="category" x-model="scope" />
                        </div>
                    </div>

                    <div x-show="scope === 'category'" x-cloak>
                        <x-ui.forms.select label="Categoría" name="category_id" placeholder="Seleccione la categoría..."
                            :error="$errors->first('category_id')">
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('category_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    <x-ui.forms.toggle label="Conteo ciego" name="blind" value="1" :checked="(bool) old('blind')"
                        description="Quien cuenta no ve la existencia del sistema; las diferencias las ve quien revisa. Útil cuando cuenta un empleado y revisa otra persona." />

                    <x-ui.forms.textarea label="Notas" name="notes" :rows="2" placeholder="Ej: Conteo de fin de mes, pasillo 3"
                        :error="$errors->first('notes')">{{ old('notes') }}</x-ui.forms.textarea>

                    <x-ui.alert variant="neutral" title="Consejo:">
                        cuenta al cierre o en una bodega sin ventas, y evita transferencias mientras dure el conteo.
                    </x-ui.alert>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                    <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Crear y empezar a contar</x-ui.button>
                </div>
            </form>
        </x-modal>
    @endcan
</div>
