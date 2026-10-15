@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
@endphp

<div>
    <x-ui.page-header title="Gastos" :description="'Gastado con los filtros actuales: '.$money($spentTotal).'. No incluye los anulados.'"
        :count="$expenses->total()" countLabel="gastos">
        @can('expenses.create')
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.expenses.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Registrar gasto
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$expenses"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Categoría" filterKey="category" :options="$categories" placeholder="Todas" />
                    <x-data-table.filter-select label="Pago" filterKey="payment_condition" :options="$conditions" placeholder="Todos" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                    @if($vendors)
                        <x-data-table.filter-select label="Proveedor" filterKey="vendor_id" :options="$vendors" placeholder="Todos" />
                    @endif
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha del gasto" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($expenses as $expense)
            <tr @class(['hover:bg-slate-50 transition-colors duration-150', 'opacity-60' => $expense->isVoided()]) wire:key="expense-{{ $expense->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <a href="{{ route('finance.expenses.show', $expense) }}" class="font-mono font-bold text-zertix-primary-700 hover:underline">{{ $expense->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="expense_date" :visible="$visibleColumns">
                    <span class="text-xs font-medium text-slate-700">{{ $expense->expense_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="concept" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $expense->concept }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="category" :visible="$visibleColumns">
                    <x-ui.badge variant="slate" size="sm" :dot="false">{{ $expense->category_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="vendor" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $expense->vendor?->display_name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="condition" :visible="$visibleColumns">
                    @if($expense->isCash())
                        <span class="block text-xs text-slate-700">Contado</span>
                        <span class="block text-[11px] text-slate-400">{{ $expense->account?->name }}</span>
                    @else
                        <span class="block text-xs text-slate-700">Crédito</span>
                        @if($expense->payable)
                            <a href="{{ route('finance.payables.show', $expense->payable) }}" class="block text-[11px] font-mono text-zertix-primary-700 hover:underline">{{ $expense->payable->number }}</a>
                        @endif
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-bold tabular-nums', 'text-slate-900' => ! $expense->isVoided(), 'text-slate-400 line-through' => $expense->isVoided()])>
                        {{ $money($expense->amount) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    @if($expense->isVoided())
                        <x-ui.badge variant="error" size="sm" :dot="false">Anulado</x-ui.badge>
                    @elseif($expense->payable)
                        <x-ui.badge :variant="$expense->payable->status_variant" size="sm" :dot="false">{{ $expense->payable->status_label }}</x-ui.badge>
                    @else
                        <x-ui.badge variant="success" size="sm" :dot="false">Pagado</x-ui.badge>
                    @endif
                </x-data-table.cell>

                <x-data-table.cell column="supplier_invoice" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $expense->supplier_invoice ?? '—' }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.button href="{{ route('finance.expenses.show', $expense) }}" variant="secondary" appearance="ghost" size="sm" iconLeft="heroicon-o-eye">
                        Ver
                    </x-ui.button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-receipt-percent" title="No hay gastos"
                        description="Registra lo que consume el negocio: luz, alquiler, gasolina, nómina…" />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>
</div>
