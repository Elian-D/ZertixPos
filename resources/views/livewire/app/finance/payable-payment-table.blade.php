@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
@endphp

<div>
    <x-ui.page-header title="Pagos a proveedores" :description="'Pagado con los filtros actuales: '.$money($paidTotal).'. No incluye los anulados.'"
        :count="$payments->total()" countLabel="pagos">
        @can('payables.pay')
            <x-slot:actions>
                <x-ui.button variant="primary" iconLeft="heroicon-s-banknotes" x-data x-on:click="$dispatch('open-modal', 'pick-payable')">
                    Pagar deuda
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$payments"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="$this->activeFilterCount()">
                <x-data-table.filter-group title="Filtros Principales">
                    <x-data-table.filter-select label="Desde la cuenta" filterKey="account_id" :options="$accounts" placeholder="Todas" />
                    <x-data-table.filter-select label="Estado" filterKey="status" :options="$statuses" placeholder="Todos" />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Fecha" collapsed>
                    <x-data-table.filter-date-range label="Fecha del pago" fromKey="from_date" toKey="to_date" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($payments as $payment)
            <tr @class(['hover:bg-slate-50 transition-colors duration-150', 'opacity-60' => $payment->isVoided()]) wire:key="payment-{{ $payment->id }}">
                <x-data-table.cell column="number" :visible="$visibleColumns">
                    <span class="font-mono font-bold text-slate-800">{{ $payment->number }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="payment_date" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $payment->payment_date->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="vendor" :visible="$visibleColumns">
                    <span class="font-medium text-slate-800">{{ $payment->payable->vendor?->display_name ?? 'Sin proveedor' }}</span>
                    <span class="block text-xs text-slate-500 max-w-xs truncate">{{ $payment->payable->concept }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="payable" :visible="$visibleColumns">
                    <a href="{{ route('finance.payables.show', $payment->payable_id) }}" class="font-mono text-xs font-semibold text-zertix-primary-700 hover:underline">{{ $payment->payable->number }}</a>
                </x-data-table.cell>

                <x-data-table.cell column="account" :visible="$visibleColumns">
                    <span class="text-xs text-slate-700">{{ $payment->account?->name ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="reference" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $payment->reference ?? '—' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="amount" :visible="$visibleColumns" class="px-4 py-3.5 text-right">
                    <span @class(['font-bold tabular-nums', 'text-slate-900' => ! $payment->isVoided(), 'text-slate-400 line-through' => $payment->isVoided()])>
                        {{ $money($payment->amount) }}
                    </span>
                </x-data-table.cell>

                <x-data-table.cell column="status" :visible="$visibleColumns" class="px-4 py-3.5 text-center">
                    <x-ui.badge :variant="$payment->status_variant" size="sm" :dot="false">{{ $payment->status_label }}</x-ui.badge>
                </x-data-table.cell>

                <x-data-table.cell column="creator" :visible="$visibleColumns">
                    <span class="text-xs text-slate-500">{{ $payment->creator->name ?? '—' }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <x-ui.button href="{{ route('finance.payments.receipt', $payment) }}" target="_blank" variant="secondary" appearance="ghost" size="sm" iconLeft="heroicon-o-printer">
                        Recibo
                    </x-ui.button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" icon="heroicon-o-banknotes" title="No hay pagos"
                        description="Los pagos se registran desde cada cuenta por pagar." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    {{-- Pagar deuda: elegir la cuenta por pagar abierta y pasar a su vista de pago --}}
    @can('payables.pay')
        <x-modal name="pick-payable" maxWidth="2xl">
            <div class="p-6" x-data="{ q: '' }">
                <h3 class="text-lg font-semibold text-gray-900">Pagar deuda</h3>
                <p class="mt-1 text-sm text-gray-500">Elige qué cuenta por pagar vas a pagar. La más próxima a vencer va primero.</p>

                @if($openPayables->isEmpty())
                    <x-ui.alert variant="neutral" class="mt-5">No hay deudas pendientes con proveedores.</x-ui.alert>
                @else
                    <div class="mt-5">
                        <x-ui.forms.input name="pick_search" x-model="q" iconLeft="heroicon-s-magnifying-glass"
                            placeholder="Buscar por número, proveedor o concepto…" autocomplete="off" />
                    </div>

                    <ul class="mt-4 max-h-96 overflow-y-auto divide-y divide-gray-100 rounded-xl border border-gray-100">
                        @foreach($openPayables as $payable)
                            @php $haystack = mb_strtolower($payable->number.' '.($payable->vendor?->display_name ?? '').' '.$payable->concept); @endphp
                            <li x-show="! q || @js($haystack).includes(q.toLowerCase())">
                                <a href="{{ route('finance.payables.pay.form', $payable) }}"
                                    class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-900">
                                            <span class="font-mono">{{ $payable->number }}</span> · {{ $payable->vendor?->display_name ?? 'Sin proveedor' }}
                                        </span>
                                        <span class="block text-xs text-gray-500 truncate">{{ $payable->concept }}</span>
                                    </span>
                                    <span class="text-right shrink-0">
                                        <span class="block text-sm font-bold tabular-nums text-gray-900">{{ $money($payable->balance) }}</span>
                                        <span @class(['block text-xs', 'text-state-error font-medium' => $payable->isOverdue(), 'text-gray-500' => ! $payable->isOverdue()])>
                                            {{ $payable->isOverdue() ? 'Venció' : 'Vence' }} {{ $payable->due_date->format('d/m/Y') }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-6 flex justify-end">
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cerrar</x-ui.button>
                </div>
            </div>
        </x-modal>
    @endcan
</div>
