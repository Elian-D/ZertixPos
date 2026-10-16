{{-- Proveedor — show (v1.5.0 REQ-4.1, /filament-show) con pestañas: Resumen, Órdenes de
     compra, Recepciones (Fase 5), Cuentas por pagar (cada una con sus pagos) y Gastos.
     Recibe $vendor (con defaultAccount), $orders (con warehouse e items_count), $receptions
     (con order y payable), $payables (con source y payments.account), $expenses (con
     account) y $summary. --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $user = auth()->user();
    $canOrder = $vendor->is_active && $user->can('purchase_orders.create');
    $canExpense = $vendor->is_active && module_enabled('sales.payables') && $user->can('expenses.create');
    $canSeeOrders = $user->can('purchase_orders.view');
    $tabs = array_values(array_filter([
        ['name' => 'summary', 'label' => 'Resumen', 'icon' => 'heroicon-o-building-storefront'],
        $canSeeOrders ? ['name' => 'orders', 'label' => 'Órdenes de compra', 'icon' => 'heroicon-o-clipboard-document-list', 'count' => $summary['orders_count']] : null,
        $canSeeOrders ? ['name' => 'receptions', 'label' => 'Recepciones', 'icon' => 'heroicon-o-inbox-arrow-down', 'count' => $summary['receptions_count']] : null,
        ['name' => 'payables', 'label' => 'Cuentas por pagar', 'icon' => 'heroicon-o-document-text', 'count' => $summary['payables_count']],
        ['name' => 'expenses', 'label' => 'Gastos', 'icon' => 'heroicon-o-receipt-percent', 'count' => $summary['expenses_count']],
    ]));
@endphp

<x-app-layout :title="$vendor->display_name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$vendor->display_name" :description="$vendor->trade_name ? $vendor->business_name : ($vendor->tax_id ? $vendor->tax_identifier_label.' '.$vendor->tax_id : 'Proveedor')">
            <x-slot:actions>
                <x-ui.button href="{{ route('purchases.vendors.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @can('vendors.edit')
                    <x-ui.button href="{{ route('purchases.vendors.edit', $vendor) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @endcan
                @if($canOrder)
                    <x-ui.button href="{{ route('purchases.orders.create', ['vendor' => $vendor->id]) }}" variant="primary" iconLeft="heroicon-s-plus">
                        Nueva orden
                    </x-ui.button>
                @endif
            </x-slot:actions>

            @if($canExpense)
                <x-slot:secondary>
                    <x-ui.button href="{{ route('finance.expenses.create', ['vendor' => $vendor->id]) }}" variant="secondary" appearance="ghost"
                        class="w-full justify-start" iconLeft="heroicon-s-receipt-percent">
                        Registrar gasto
                    </x-ui.button>
                </x-slot:secondary>
            @endif
        </x-ui.page-header>

        @unless($vendor->is_active)
            <x-ui.alert variant="neutral" icon="heroicon-s-no-symbol">Proveedor inactivo: no aparece al registrar gastos ni compras.</x-ui.alert>
        @endunless

        {{-- Totales --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui.stat label="Le debes" :value="$money($summary['pending'])"
                :variant="$summary['pending'] > 0 ? 'error' : 'default'"
                :hint="$summary['overdue'] ? $summary['overdue'].' '.($summary['overdue'] === 1 ? 'cuenta vencida' : 'cuentas vencidas') : 'Nada vencido'" />
            <x-ui.stat label="Comprado" :value="$money($summary['purchased'])"
                :hint="'Recibido en compras · '.$money($summary['spent']).' en gastos'" />
            <x-ui.stat label="Calificación" :value="$vendor->rating_label" hint="Sale de los despachos que se califican al recibir mercancía" />
        </div>

        <x-ui.infolist.tabs :tabs="$tabs">
            {{-- Resumen --}}
            <x-ui.infolist.tab name="summary" class="!space-y-6">
                <x-ui.infolist.section title="Datos generales" icon="heroicon-o-identification" :cols="3" flat>
                    <x-ui.infolist.entry label="Razón social" :value="$vendor->business_name" strong />
                    <x-ui.infolist.entry label="Nombre comercial" :value="$vendor->trade_name" />
                    <x-ui.infolist.entry label="RNC / Cédula" :value="$vendor->tax_id ? $vendor->tax_identifier_label.' '.$vendor->tax_id : null" />
                    <x-ui.infolist.entry label="Estado">
                        <x-ui.badge :variant="$vendor->is_active ? 'success' : 'slate'" size="sm" :dot="false">{{ $vendor->is_active ? 'Activo' : 'Inactivo' }}</x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Calificación">
                        <x-ui.badge :variant="$vendor->rating_variant" size="sm" :dot="false">{{ $vendor->rating_label }}</x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Creado" :value="$vendor->created_at->format('d/m/Y')" />
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Contacto" icon="heroicon-o-phone" :cols="3" flat>
                    <x-ui.infolist.entry label="Persona" :value="$vendor->contact_name" />
                    <x-ui.infolist.entry label="Teléfono" :value="$vendor->phone" />
                    <x-ui.infolist.entry label="Correo" :value="$vendor->email" />
                    <x-ui.infolist.entry label="Dirección" :value="$vendor->address" full />
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Términos financieros" icon="heroicon-o-banknotes" :cols="3" flat>
                    <x-ui.infolist.entry label="Condición" :value="$vendor->condition_label.($vendor->payment_condition === 'credit' ? ' · '.$vendor->credit_days.' días' : '')" />
                    <x-ui.infolist.entry label="Cuenta de pago sugerida" :value="$vendor->defaultAccount?->name" />
                    <x-ui.infolist.entry label="Notas" :value="$vendor->notes" full />
                </x-ui.infolist.section>
            </x-ui.infolist.tab>

            @if($canSeeOrders)
                {{-- Órdenes de compra --}}
                <x-ui.infolist.tab name="orders">
                    <x-ui.infolist.repeatable :empty="$orders->isEmpty()" emptyIcon="heroicon-o-clipboard-document-list"
                        emptyTitle="Sin órdenes de compra" emptyDescription="Las órdenes que le hagas a este proveedor aparecen aquí.">
                        @foreach($orders as $order)
                            <x-ui.infolist.repeatable-item :cols="5">
                                <x-ui.infolist.entry label="Orden" strong :href="route('purchases.orders.show', $order)">
                                    {{ $order->number }}
                                    <span class="block text-xs font-normal text-gray-500">{{ $order->order_date->format('d/m/Y') }}</span>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Almacén" :value="$order->warehouse->name ?? null" />
                                <x-ui.infolist.entry label="Condición" :value="$order->condition_label.' · '.$order->items_count.' '.($order->items_count === 1 ? 'línea' : 'líneas')" />
                                <x-ui.infolist.entry label="Total" :value="$money($order->total)" strong />
                                <x-ui.infolist.entry label="Estado" class="sm:text-right">
                                    <x-ui.badge :variant="$order->status_variant" size="sm" :dot="false">{{ $order->status_label }}</x-ui.badge>
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>
                </x-ui.infolist.tab>

                {{-- Recepciones --}}
                <x-ui.infolist.tab name="receptions">
                    <x-ui.infolist.repeatable :empty="$receptions->isEmpty()" emptyIcon="heroicon-o-inbox-arrow-down"
                        emptyTitle="Sin recepciones" emptyDescription="Cada entrega que recibas de sus órdenes aparece aquí, con su calificación.">
                        @foreach($receptions as $reception)
                            <x-ui.infolist.repeatable-item :cols="5">
                                <x-ui.infolist.entry label="Recepción" strong :href="route('purchases.receptions.show', $reception)">
                                    {{ $reception->number }}
                                    <span class="block text-xs font-normal text-gray-500">{{ $reception->reception_date->format('d/m/Y') }} · orden {{ $reception->order->number ?? '' }}</span>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Factura" :value="$reception->supplier_invoice ?? $reception->supplier_ncf" />
                                <x-ui.infolist.entry label="Total" :value="$money($reception->total)" strong />
                                <x-ui.infolist.entry label="Cuenta por pagar">
                                    @if($reception->payable)
                                        <x-ui.badge :variant="$reception->payable->status_variant" size="sm" :dot="false">{{ $reception->payable->number }} · {{ $reception->payable->status_label }}</x-ui.badge>
                                    @else
                                        <span class="text-gray-400">Sin deuda</span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Calificación" class="sm:text-right">
                                    @if($reception->rating)
                                        <x-ui.badge :variant="$reception->rating_variant" size="sm" :dot="false">{{ $reception->rating }}</x-ui.badge>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>
                </x-ui.infolist.tab>
            @endif

            {{-- Cuentas por pagar --}}
            <x-ui.infolist.tab name="payables">
                <x-ui.infolist.repeatable :empty="$payables->isEmpty()" emptyIcon="heroicon-o-document-text"
                    emptyTitle="Sin cuentas por pagar" emptyDescription="Nacen de un gasto a crédito o, más adelante, de recibir una compra a crédito.">
                    @foreach($payables as $payable)
                        <x-ui.infolist.repeatable-item :cols="5">
                            <x-ui.infolist.entry label="CxP" strong
                                :href="$user->can('payables.view') && module_enabled('sales.payables') ? route('finance.payables.show', $payable) : null">
                                {{ $payable->number }}
                                <span class="block text-xs font-normal text-gray-500 truncate">{{ $payable->concept }}</span>
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Vence" :value="$payable->due_date->format('d/m/Y')" />
                            <x-ui.infolist.entry label="Total" :value="$money($payable->total_amount)" />
                            <x-ui.infolist.entry label="Pendiente">
                                <span @class(['font-semibold tabular-nums', 'text-state-warning' => $payable->isOpen()])>{{ $money($payable->balance) }}</span>
                                @if($payable->payments->isNotEmpty())
                                    <span class="block text-xs text-gray-500">{{ $payable->payments->count() }} {{ $payable->payments->count() === 1 ? 'pago' : 'pagos' }}</span>
                                @endif
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Estado" class="sm:text-right">
                                <x-ui.badge :variant="$payable->status_variant" size="sm" :dot="false">{{ $payable->status_label }}</x-ui.badge>
                            </x-ui.infolist.entry>
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>
            </x-ui.infolist.tab>

            {{-- Gastos --}}
            <x-ui.infolist.tab name="expenses">
                <x-ui.infolist.repeatable :empty="$expenses->isEmpty()" emptyIcon="heroicon-o-receipt-percent"
                    emptyTitle="Sin gastos" emptyDescription="Los gastos que registres con este proveedor aparecen aquí.">
                    @foreach($expenses as $expense)
                        <x-ui.infolist.repeatable-item :cols="5" :class="$expense->isVoided() ? 'opacity-60' : ''">
                            <x-ui.infolist.entry label="Fecha" :value="$expense->expense_date->format('d/m/Y')" />
                            <x-ui.infolist.entry label="Concepto" class="col-span-2" strong
                                :href="$user->can('expenses.view') && module_enabled('sales.payables') ? route('finance.expenses.show', $expense) : null">
                                {{ $expense->concept }}
                                <span class="block text-xs font-normal text-gray-500">{{ $expense->number }} · {{ $expense->category_label }}</span>
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Monto" :value="$money($expense->amount)" strong />
                            <x-ui.infolist.entry label="Condición" class="sm:text-right">
                                <x-ui.badge :variant="$expense->isVoided() ? 'error' : ($expense->isCash() ? 'slate' : 'info')" size="sm" :dot="false">
                                    {{ $expense->isVoided() ? 'Anulado' : $expense->condition_label }}
                                </x-ui.badge>
                            </x-ui.infolist.entry>
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>
            </x-ui.infolist.tab>
        </x-ui.infolist.tabs>
    </div>
</x-app-layout>
