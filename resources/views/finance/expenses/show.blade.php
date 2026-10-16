{{-- Gasto — show (v1.5.0 REQ-4.4, /filament-show). Fila 1: el gasto | cómo se pagó (la
     cuenta de contado, o su cuenta por pagar con los pagos). Anular devuelve el dinero o
     anula la CxP con sus pagos.
     Recibe $expense (vendor, account, creator, voider, payable.payments.account). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $user = auth()->user();
    $payable = $expense->payable;
    $canVoid = ! $expense->isVoided() && $user->can('expenses.void');
    $activePayments = $payable?->payments->where('status', 'active') ?? collect();
@endphp

<x-app-layout :title="'Gasto '.$expense->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Gasto '.$expense->number" :description="$expense->concept.' · '.$expense->category_label.' · '.$expense->expense_date->format('d/m/Y')">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.expenses.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($payable && $payable->isOpen() && $user->can('payables.pay'))
                    <x-ui.button href="{{ route('finance.payables.pay.form', $payable) }}" variant="primary" iconLeft="heroicon-s-banknotes">
                        Pagar
                    </x-ui.button>
                @endif
                @if($canVoid)
                    <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-x-circle"
                        x-data x-on:click="$dispatch('open-modal', 'void-expense')">
                        Anular
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if($expense->isVoided())
            <x-ui.alert variant="error">
                Anulado por <strong>{{ $expense->voider->name ?? '—' }}</strong> el {{ $expense->voided_at?->format('d/m/Y h:i A') }}: {{ $expense->void_reason }}.
                {{ $expense->isCash() ? 'El dinero volvió a la cuenta.' : 'Su cuenta por pagar y sus pagos quedaron anulados.' }}
            </x-ui.alert>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Gasto" icon="heroicon-o-receipt-percent" :cols="2">
                <x-ui.infolist.entry label="Número" :value="$expense->number" strong />
                <x-ui.infolist.entry label="Concepto" :value="$expense->concept" strong />
                <x-ui.infolist.entry label="Categoría" :value="$expense->category_label" />
                <x-ui.infolist.entry label="Monto">
                    <span class="text-lg font-bold tabular-nums text-gray-900">{{ $money($expense->amount) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Fecha" :value="$expense->expense_date->format('d/m/Y')" />
                <x-ui.infolist.entry label="Estado">
                    <x-ui.badge :variant="$expense->status_variant" size="sm" :dot="false">{{ $expense->status_label }}</x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Proveedor" :value="$expense->vendor?->display_name"
                    :href="$expense->vendor && ! $expense->vendor->trashed() && module_enabled('purchases') && $user->can('vendors.view') ? route('purchases.vendors.show', $expense->vendor) : null" />
                <x-ui.infolist.entry label="Factura o NCF" :value="$expense->supplier_invoice" />
                <x-ui.infolist.entry label="Registró" :value="($expense->creator->name ?? '—').' · '.$expense->created_at->format('d/m/Y h:i A')" full />
                <x-ui.infolist.entry label="Nota" :value="$expense->notes" full />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Pago" icon="heroicon-o-banknotes" :cols="2">
                <x-ui.infolist.entry label="Condición">
                    <x-ui.badge :variant="$expense->isCash() ? 'slate' : 'info'" size="sm" :dot="false">{{ $expense->condition_label }}</x-ui.badge>
                </x-ui.infolist.entry>
                @if($expense->isCash())
                    <x-ui.infolist.entry label="Salió de" :value="$expense->account?->name"
                        :href="$expense->account && $user->can('financial_accounts.view') ? route('finance.treasury.show', $expense->account) : null" />
                @elseif($payable)
                    <x-ui.infolist.entry label="Cuenta por pagar" :value="$payable->number" strong
                        :href="$user->can('payables.view') ? route('finance.payables.show', $payable) : null" />
                    <x-ui.infolist.entry label="Vence" :value="$payable->due_date->format('d/m/Y')" />
                    <x-ui.infolist.entry label="Pagado" :value="$money($payable->paid_amount)" />
                    <x-ui.infolist.entry label="Pendiente">
                        <span @class(['font-semibold tabular-nums', 'text-state-warning' => $payable->isOpen()])>{{ $money($payable->balance) }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Estado de la CxP">
                        <x-ui.badge :variant="$payable->status_variant" size="sm" :dot="false">{{ $payable->status_label }}</x-ui.badge>
                    </x-ui.infolist.entry>
                @endif
            </x-ui.infolist.section>
        </div>

        @if($payable && $payable->payments->isNotEmpty())
            <x-ui.infolist.section title="Pagos" icon="heroicon-o-queue-list" :cols="0">
                <x-ui.infolist.repeatable>
                    @foreach($payable->payments as $payment)
                        <x-ui.infolist.repeatable-item :cols="4" :class="$payment->isVoided() ? 'opacity-60' : ''">
                            <x-ui.infolist.entry label="Pago" :value="$payment->number" strong />
                            <x-ui.infolist.entry label="Fecha" :value="$payment->payment_date->format('d/m/Y')" />
                            <x-ui.infolist.entry label="Cuenta" :value="$payment->account?->name" />
                            <x-ui.infolist.entry label="Monto" class="sm:text-right">
                                <span class="font-semibold tabular-nums">{{ $money($payment->amount) }}</span>
                                @if($payment->isVoided())<span class="block text-xs text-state-error">Anulado</span>@endif
                            </x-ui.infolist.entry>
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>
            </x-ui.infolist.section>
        @endif
    </div>

    @if($canVoid)
        <x-ui.confirm-modal name="void-expense" title="¿Anular el gasto?" :route="route('finance.expenses.void', $expense)" method="PATCH"
            variant="error" confirmLabel="Anular gasto" :show="$errors->has('void_reason')"
            :description="$expense->isCash()
                ? 'El dinero vuelve a '.($expense->account->name ?? 'la cuenta').'. El gasto queda anulado con tu motivo.'
                : 'Se anula su cuenta por pagar'.($activePayments->isNotEmpty() ? ' y sus '.$activePayments->count().' pagos (el dinero vuelve a cada cuenta)' : '').'.'">
            <x-ui.forms.textarea label="Motivo" name="void_reason" :rows="2" required placeholder="Ej. Se registró dos veces"
                :error="$errors->first('void_reason')">{{ old('void_reason') }}</x-ui.forms.textarea>
        </x-ui.confirm-modal>
    @endif
</x-app-layout>
