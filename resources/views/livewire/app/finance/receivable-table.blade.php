<div>

    <x-ui.page-header
        title="Cuentas por Cobrar (Facturación)"
        description="Gestiona las facturas pendientes de cobro y su estado de pago."
        :count="$items->total()"
        countLabel="cuentas"
    />

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
                    label="Estado de Factura" filterKey="status"
                    :options="$statuses"
                    placeholder="Todos los estados" />

                <x-data-table.filter-select
                    label="Antigüedad" filterKey="overdue"
                    :options="['yes' => 'Vencidas', 'no' => 'Al día']"
                    placeholder="Todas" />

                <x-data-table.filter-range
                    label="Rango de Saldo Pendiente" fromKey="min_balance" toKey="max_balance"
                    prefix="{{ config('regional.currency_symbol') }}" />

            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($items as $item)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="emission_date" :visible="$visibleColumns">
                    <span class="text-slate-600">{{ $item->emission_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="due_date" :visible="$visibleColumns">
                    <span class="{{ $item->is_overdue ? 'text-red-600 font-bold' : 'text-slate-600' }}">
                        {{ $item->due_date->format('d/m/Y') }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="document_number" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-zertix-primary-700">{{ $item->document_number }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="client" :visible="$visibleColumns">
                    <div class="font-medium text-slate-900">{{ $item->client->name ?? 'N/A' }}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">{{ $item->client->tax_id ?? 'Sin RNC/Cédula' }}</div>
                </x-data-table.cell>

                <x-data-table.cell column="description" :visible="$visibleColumns" class="px-4 py-3.5 max-w-[200px] truncate">
                    <span class="text-slate-500">{{ $item->description }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="total_amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right font-medium text-slate-600">
                    {{ number_format($item->total_amount, 2) }}
                </x-data-table.cell>

                <x-data-table.cell column="current_balance" :visible="$visibleColumns" class="px-4 py-3.5 text-right font-bold">
                    @php
                        $percentageLeft = ($item->total_amount > 0) ? ($item->current_balance / $item->total_amount) * 100 : 0;
                        $balanceColor = 'text-slate-900';
                        if ($item->current_balance <= 0) $balanceColor = 'text-emerald-600';
                        elseif ($percentageLeft <= 30) $balanceColor = 'text-blue-600';
                        elseif ($percentageLeft <= 70) $balanceColor = 'text-amber-600';
                        else $balanceColor = 'text-red-600';
                    @endphp
                    <span class="{{ $balanceColor }}">{{ number_format($item->current_balance, 2) }}</span>
                </x-data-table.cell>

                {{-- Guard explícito, no solo :visible="$visibleColumns" — el slot de
                     x-data-table.cell se evalúa en PHP siempre, sin importar si la
                     columna está visible (Blade renderiza el contenido del slot antes
                     de pasarlo al componente). Sin este @if, $item->accountingAccount
                     se toca por fila igual con la columna oculta o con
                     accounting.advanced apagado (N+1 real, detectado en Debugbar). --}}
                @if(module_enabled('accounting.advanced'))
                    <x-data-table.cell column="accounting_account_id" :visible="$visibleColumns">
                        @if($item->client?->accounting_account_id)
                            <p class="text-xs font-mono text-zertix-primary-600">{{ $item->client->accountingAccount->code }}</p>
                        @else
                            <p class="text-xs font-mono text-slate-600">{{ $item->accountingAccount->code ?? '1.1.02' }}</p>
                        @endif
                    </x-data-table.cell>
                @endif

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="match($item->status) {
                        \App\Models\Accounting\Receivable::STATUS_UNPAID => 'error',
                        \App\Models\Accounting\Receivable::STATUS_PARTIAL => 'warning',
                        \App\Models\Accounting\Receivable::STATUS_PAID => 'success',
                        \App\Models\Accounting\Receivable::STATUS_CANCELLED => 'slate',
                        default => 'info',
                    }" size="sm" :dot="false">
                        {{ $item->status_label }}
                    </x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-[11px] text-slate-400">{{ $item->updated_at->diffForHumans() }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Categoría C (docs/analisis/politica-soft-deletes.md) — CxC es bitácora
                             de deuda, nunca se borra ni se archiva. Solo lectura. --}}
                        <x-ui.button
                            appearance="ghost" variant="secondary" size="sm" icon="heroicon-s-eye"
                            x-data @click="$dispatch('open-modal', 'view-receivable-{{ $item->id }}')"
                            aria-label="Ver detalle" title="Ver Detalle" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-banknotes" title="No se encontraron cuentas por cobrar"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    @include('accounting.receivables.partials.modals', ['items' => $items])
</div>
