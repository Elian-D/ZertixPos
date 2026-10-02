{{-- Detalle del cobro — patrón Infolist (/filament-show, docs/ui/infolist.md) con
     columna lateral: la vista previa del recibo (iframe, ?preview=1). Recibe
     $payment (client, tipoPago, creator, receivable, posSession.terminal cargados). --}}
@use('App\Models\Accounting\ClientCollection')
@use('App\Models\Configuration\TipoPago')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $isActive = $payment->status === ClientCollection::STATUS_ACTIVE;
    $receivable = $payment->receivable;
    $saleId = $receivable && $receivable->reference_type === \App\Models\Sales\Sale::class ? $receivable->reference_id : null;
    $slug = $payment->tipoPago?->slug;
    $terminal = $payment->posSession?->terminal;
@endphp

<x-app-layout :title="'Recibo '.$payment->receipt_number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Recibo '.$payment->receipt_number" description="Ver cobro">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.collections.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button :href="route('finance.collections.print', ['payment' => $payment, 'format' => 'letter', 'download' => 1])"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-down-tray">
                    PDF
                </x-ui.button>
                <x-ui.button href="{{ route('finance.collections.print', $payment) }}" target="_blank"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    Imprimir recibo
                </x-ui.button>
                @if($isActive)
                    @can('collections.cancel')
                        <x-ui.button variant="error" iconLeft="heroicon-s-x-circle"
                            x-data @click="$dispatch('open-modal', 'confirm-cancel-payment-{{ $payment->id }}')">
                            Anular
                        </x-ui.button>
                    @endcan
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @unless($isActive)
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-x-circle class="w-5 h-5 shrink-0" />
                Cobro anulado — el monto se devolvió al saldo de la cuenta por cobrar.
            </div>
        @endunless

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 flex flex-col gap-6 min-w-0">

                <x-ui.infolist.section title="Datos del cobro" icon="heroicon-o-banknotes" :cols="3">
                    <x-ui.infolist.entry label="Monto cobrado">
                        <span @class(['text-2xl font-bold', 'text-emerald-600' => $isActive, 'text-gray-400 line-through' => ! $isActive])>{{ $money($payment->amount) }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Estado">
                        <x-ui.badge :variant="$isActive ? 'success' : 'error'" size="sm" :dot="false">
                            {{ ClientCollection::getStatuses()[$payment->status] ?? $payment->status }}
                        </x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Método">
                        @if($payment->tipoPago)
                            <x-ui.badge size="sm"
                                :hex="TipoPago::getBadgeHexColors()[$slug] ?? TipoPago::getDefaultBadgeHex()"
                                :icon="TipoPago::getBadgeIcons()[$slug] ?? TipoPago::getDefaultBadgeIcon()">
                                {{ $payment->tipoPago->nombre }}
                            </x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Recibo" :value="$payment->receipt_number" strong />
                    <x-ui.infolist.entry label="Fecha de pago" :value="$payment->payment_date?->format('d/m/Y')" />
                    <x-ui.infolist.entry label="Referencia" :value="$payment->reference" />
                    <x-ui.infolist.entry label="Cliente" :value="$payment->client?->display_name" strong
                        :href="$payment->client && auth()->user()->can('clients.view') ? route('clients.show', $payment->client) : null" />
                    <x-ui.infolist.entry label="Registrado por">
                        {{ $payment->creator?->name }}
                        <span class="block text-xs text-gray-400">{{ $payment->created_at->format('d/m/Y h:i A') }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Origen">
                        @if($terminal)
                            TPV · {{ $terminal->name }}
                            @if(auth()->user()->can('pos_sessions.history'))
                                <a href="{{ route('sales.pos.sessions.show', $payment->pos_session_id) }}" class="block text-xs text-zertix-primary-700 hover:underline">Turno {{ $payment->posSession?->number }}</a>
                            @else
                                <span class="block text-xs text-gray-400">Turno {{ $payment->posSession?->number }}</span>
                            @endif
                        @else
                            Backoffice
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Nota" :value="$payment->note" full />
                </x-ui.infolist.section>

                <x-ui.infolist.section title="Aplicado a" icon="heroicon-o-document-text" :cols="3"
                    description="La cuenta por cobrar que este cobro abonó.">
                    <x-ui.infolist.entry label="Cuenta por cobrar" :value="$receivable?->number" strong
                        :href="$receivable && auth()->user()->can('receivables.view') ? route('finance.receivables.show', $receivable) : null" />
                    <x-ui.infolist.entry label="Venta origen" :value="$receivable?->document_number"
                        :href="$saleId && auth()->user()->can('sales.view') ? route('sales.show', $saleId) : null" />
                    <x-ui.infolist.entry label="Estado de la cuenta">
                        @if($receivable)
                            <x-ui.badge :variant="$receivable->status_variant" size="sm" :dot="false">{{ $receivable->status_label }}</x-ui.badge>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Saldo actual de la cuenta">
                        @if($receivable)
                            <span @class(['font-semibold', 'text-amber-600' => $receivable->current_balance > 0, 'text-emerald-600' => $receivable->current_balance <= 0])>
                                {{ $money($receivable->current_balance) }}
                            </span>
                        @endif
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Vencimiento" :value="$receivable?->due_date?->format('d/m/Y')" />
                    <x-ui.infolist.entry label="Monto de la cuenta" :value="$receivable ? $money($receivable->total_amount) : null" />
                </x-ui.infolist.section>
            </div>

            {{-- Columna lateral: vista previa del recibo --}}
            <x-ui.infolist.section title="Recibo" icon="heroicon-o-printer" :cols="0" class="lg:sticky lg:top-6">
                <div class="-m-5 sm:-m-6 bg-slate-100 p-4 flex justify-center rounded-b-2xl">
                    <iframe src="{{ route('finance.collections.print', ['payment' => $payment, 'preview' => 1]) }}"
                            title="Recibo {{ $payment->receipt_number }}"
                            class="w-full max-w-[320px] h-[520px] bg-white rounded shadow"></iframe>
                </div>
            </x-ui.infolist.section>
        </div>
    </div>

    {{-- Modal de anular (mismo partial del listado) --}}
    @include('finance.collections.partials.modals', ['items' => collect([$payment])])
</x-app-layout>
