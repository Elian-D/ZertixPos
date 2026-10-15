{{-- Cuenta por pagar — show (v1.5.0 REQ-4.2, /filament-show). Fila 1: la deuda | su
     saldo. Fila 2: pagos (EGR), cada uno con su recibo y anular. La CxP no se anula sola:
     se anula con su documento de origen (el gasto).
     Recibe $payable (vendor, source, creator, voider, payments.account/creator/voider). --}}
@use('App\Models\Accounting\Payable')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $user = auth()->user();
    $origin = $payable->origin;
    $canVoidPayment = $user->can('payables.void_payment');
    $failedVoid = $errors->has('void_reason') ? old('_form') : null;
@endphp

<x-app-layout :title="'Cuenta por pagar '.$payable->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Cuenta por pagar '.$payable->number" :description="$payable->vendor?->display_name ?? $payable->concept">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.payables.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($payable->isOpen() && $user->can('payables.pay'))
                    <x-ui.button href="{{ route('finance.payables.pay.form', $payable) }}" variant="primary" iconLeft="heroicon-s-banknotes">
                        Registrar pago
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if($payable->status === Payable::STATUS_VOIDED)
            <x-ui.alert variant="error">
                Anulada el {{ $payable->voided_at?->format('d/m/Y h:i A') }} junto con su gasto: {{ $payable->void_reason }}.
            </x-ui.alert>
        @elseif($payable->isOverdue())
            <x-ui.alert variant="warning">Venció el {{ $payable->due_date->format('d/m/Y') }}.</x-ui.alert>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Deuda" icon="heroicon-o-document-text" :cols="2">
                <x-ui.infolist.entry label="Número" :value="$payable->number" strong />
                <x-ui.infolist.entry label="Estado">
                    <x-ui.badge :variant="$payable->status_variant" size="sm" :dot="false">{{ $payable->status_label }}</x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Proveedor" :value="$payable->vendor?->display_name"
                    :href="$payable->vendor && ! $payable->vendor->trashed() && module_enabled('purchases') && $user->can('vendors.view') ? route('purchases.vendors.show', $payable->vendor) : null" />
                <x-ui.infolist.entry label="Origen" :value="$origin['label'] ?? null"
                    :href="$origin && $user->can($origin['permission']) ? $origin['url'] : null" />
                <x-ui.infolist.entry label="Concepto" :value="$payable->concept" full />
                <x-ui.infolist.entry label="Emitida" :value="$payable->issue_date->format('d/m/Y')" />
                <x-ui.infolist.entry label="Vence" :value="$payable->due_date->format('d/m/Y')" />
                <x-ui.infolist.entry label="Factura o NCF" :value="$payable->supplier_invoice" />
                <x-ui.infolist.entry label="Registró" :value="$payable->creator->name ?? null" />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Saldo" icon="heroicon-o-currency-dollar" :cols="2">
                <x-ui.infolist.entry label="Total" :value="$money($payable->total_amount)" />
                <x-ui.infolist.entry label="Pagado" :value="$money($payable->paid_amount)" />
                <x-ui.infolist.entry label="Pendiente" full>
                    <span @class(['text-xl font-bold tabular-nums', 'text-state-warning' => $payable->isOpen(), 'text-gray-900' => ! $payable->isOpen()])>
                        {{ $money($payable->balance) }}
                    </span>
                </x-ui.infolist.entry>
            </x-ui.infolist.section>
        </div>

        <x-ui.infolist.section title="Pagos" icon="heroicon-o-queue-list" :cols="0"
            description="Anular un pago devuelve el dinero a su cuenta y el monto a lo pendiente.">
            <x-ui.infolist.repeatable :empty="$payable->payments->isEmpty()" emptyIcon="heroicon-o-banknotes"
                emptyTitle="Sin pagos" emptyDescription="Registra un pago total o parcial desde una cuenta de Cajas y Bancos.">
                @foreach($payable->payments as $payment)
                    <x-ui.infolist.repeatable-item :cols="5" :class="$payment->isVoided() ? 'opacity-60' : ''">
                        <x-ui.infolist.entry label="Pago" strong>
                            {{ $payment->number }}
                            <span class="block text-xs font-normal text-gray-500">{{ $payment->payment_date->format('d/m/Y') }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Cuenta" :value="$payment->account?->name" />
                        <x-ui.infolist.entry label="Referencia" :value="$payment->reference" />
                        <x-ui.infolist.entry label="Monto">
                            <span class="font-semibold tabular-nums">{{ $money($payment->amount) }}</span>
                            @if($payment->isVoided())
                                <span class="block text-xs text-state-error">Anulado: {{ $payment->void_reason }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="" class="sm:text-right">
                            <span class="inline-flex gap-1">
                                <x-ui.button href="{{ route('finance.payments.receipt', $payment) }}" target="_blank" variant="secondary" appearance="ghost" size="sm" iconLeft="heroicon-o-printer">
                                    Recibo
                                </x-ui.button>
                                @if(! $payment->isVoided() && $canVoidPayment)
                                    <x-ui.button variant="error" appearance="ghost" size="sm" iconLeft="heroicon-o-arrow-uturn-left"
                                        x-data x-on:click="$dispatch('open-modal', 'void-payment-{{ $payment->id }}')">
                                        Anular
                                    </x-ui.button>
                                @endif
                            </span>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>

    @if($canVoidPayment)
        @foreach($payable->payments->reject->isVoided() as $payment)
            @php $form = 'void-payment-'.$payment->id; @endphp
            <x-ui.confirm-modal :name="$form" :title="'¿Anular el pago '.$payment->number.'?'" :route="route('finance.payments.void', $payment)"
                method="PATCH" variant="error" confirmLabel="Anular pago" :show="$failedVoid === $form"
                :description="$money($payment->amount).' vuelven a '.($payment->account->name ?? 'la cuenta').' y a lo pendiente de esta cuenta por pagar.'">
                <input type="hidden" name="_form" value="{{ $form }}">
                <x-ui.forms.textarea label="Motivo" name="void_reason" :rows="2" required placeholder="Ej. Se registró de más"
                    :error="$failedVoid === $form ? $errors->first('void_reason') : null">{{ $failedVoid === $form ? old('void_reason') : '' }}</x-ui.forms.textarea>
            </x-ui.confirm-modal>
        @endforeach
    @endif
</x-app-layout>
