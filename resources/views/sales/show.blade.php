{{-- Detalle de la venta — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Fila 1: datos de la venta | totales y pago (50/50). Fila 2: productos (100%).
     Recibe $sale (relaciones cargadas en SaleController::show()) y $returnedByItem
     (sale_item_id => unidades devueltas en devoluciones no anuladas). --}}
@use('App\Models\Sales\Sale')
@use('App\Models\Configuration\TipoPago')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $isCanceled = $sale->status === Sale::STATUS_CANCELED;
    $isCredit = $sale->payment_type === Sale::PAYMENT_CREDIT;
    $canCancel = ! $isCanceled && $sale->canBeCanceled();
    $returnStatus = $sale->return_status;
    $cashPayment = $sale->payments->first(fn ($p) => $p->tipoPago?->slug === TipoPago::EFECTIVO);
    $pmHex = TipoPago::getBadgeHexColors();
    $pmIcons = TipoPago::getBadgeIcons();
@endphp

<x-app-layout :title="'Venta '.$sale->number">
    <div class="p-4 md:p-6 flex flex-col gap-6" x-data x-on:return-created.window="window.location.reload()">

        <x-ui.page-header :title="'Venta '.$sale->number" description="Ver venta">
            <x-slot:actions>
                <x-ui.button href="{{ route('sales.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button href="{{ route('sales.print-invoice', $sale) }}" target="_blank"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    Imprimir comprobante
                </x-ui.button>

                @unless($isCanceled)
                    @if($canCancel)
                        @can('sales.cancel')
                            <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-x-circle"
                                x-data @click="$dispatch('open-modal', 'confirm-cancel-sale-{{ $sale->id }}')">
                                Anular
                            </x-ui.button>
                        @endcan
                    @elseif($returnStatus !== 'full')
                        @can('returns.create')
                            <x-ui.button variant="warning" iconLeft="heroicon-s-arrow-uturn-left"
                                x-data @click="$dispatch('open-return', { saleId: {{ $sale->id }} })">
                                Devolver
                            </x-ui.button>
                        @endcan
                    @endif
                @endunless
            </x-slot:actions>
        </x-ui.page-header>

        @if($isCanceled)
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-x-circle class="w-5 h-5 shrink-0" />
                Venta anulada{{ $sale->cancellation_reason ? ' — '.$sale->cancellation_reason : '' }}.
            </div>
        @endif

        {{-- Fila 1: datos | totales y pago --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <x-ui.infolist.section title="Datos de la venta" icon="heroicon-o-document-text" :cols="2">
                <x-ui.infolist.entry label="Número" :value="$sale->number" strong />
                <x-ui.infolist.entry label="Factura" :value="$sale->invoice?->invoice_number"
                    :href="$sale->invoice && auth()->user()->can('invoices.view') ? route('finance.invoices.show', $sale->invoice) : null" />
                <x-ui.infolist.entry label="Estado">
                    <span class="inline-flex flex-wrap gap-1.5">
                        <x-ui.badge :variant="$isCanceled ? 'error' : 'success'" size="sm" :dot="false">
                            {{ Sale::getStatuses()[$sale->status] ?? $sale->status }}
                        </x-ui.badge>
                        @if($returnStatus === 'full')
                            <x-ui.badge variant="warning" size="sm" icon="heroicon-s-arrow-uturn-left">Devuelta</x-ui.badge>
                        @elseif($returnStatus === 'partial')
                            <x-ui.badge variant="info" size="sm" icon="heroicon-s-arrow-uturn-left">Devuelta parcial</x-ui.badge>
                        @endif
                    </span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Fecha y hora" :value="$sale->sale_date->format('d/m/Y h:i A')" />
                <x-ui.infolist.entry label="Cliente" :value="$sale->client?->display_name" strong
                    :href="$sale->client && auth()->user()->can('clients.view') ? route('clients.show', $sale->client) : null" />
                <x-ui.infolist.entry label="Cajero / vendedor" :value="$sale->user?->name" />
                <x-ui.infolist.entry label="Origen"
                    :value="$sale->posTerminal ? $sale->posTerminal->name.($sale->pos_session_id ? ' (turno '.$sale->posSession?->number.')' : '') : 'Backoffice'" />
                <x-ui.infolist.entry label="Almacén" :value="$sale->warehouse?->name" />
                @if(module_enabled('sales.ncf'))
                    <x-ui.infolist.entry label="NCF" :value="$sale->ncf" />
                @endif
                @if($sale->quote)
                    <x-ui.infolist.entry label="Cotización origen" :value="$sale->quote->number"
                        :href="auth()->user()->can('quotes.view') ? route('clients.quotes.show', $sale->quote->id) : null" />
                @endif
                @if($isCanceled)
                    <x-ui.infolist.entry label="Anulada por" :value="$sale->canceledBy?->name" />
                    <x-ui.infolist.entry label="Fecha de anulación" :value="$sale->canceled_at?->format('d/m/Y h:i A')" />
                    <x-ui.infolist.entry label="Motivo de anulación" :value="$sale->cancellation_reason" full />
                @endif
                @if($sale->notes)
                    <x-ui.infolist.entry label="Notas" :value="$sale->notes" full />
                @endif
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Totales y pago" icon="heroicon-o-banknotes" :cols="2">
                <x-ui.infolist.entry label="Subtotal" :value="$money($sale->total_amount)" />
                <x-ui.infolist.entry label="Descuento total">
                    <span @class(['text-amber-600' => $sale->discount_total > 0])>
                        {{ $sale->discount_total > 0 ? '-' : '' }}{{ $money($sale->discount_total) }}
                    </span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Impuestos" :value="$money($sale->tax_amount)" />
                <x-ui.infolist.entry label="Total">
                    <span class="text-xl font-bold text-zertix-primary-700">{{ $money($sale->grand_total) }}</span>
                </x-ui.infolist.entry>

                <x-ui.infolist.entry label="Tipo de venta">
                    <x-ui.badge :variant="$isCredit ? 'warning' : 'info'" size="sm" :dot="false"
                        :icon="Sale::getPaymentTypeIcons()[$sale->payment_type] ?? null">
                        {{ Sale::getPaymentTypes()[$sale->payment_type] ?? $sale->payment_type }}
                    </x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Método de pago">
                    @if($sale->payments->isNotEmpty())
                        <span class="flex flex-col gap-1.5">
                            @foreach($sale->payments as $payment)
                                @php $slug = $payment->tipoPago?->slug; @endphp
                                <span class="inline-flex flex-wrap items-center gap-2">
                                    <x-ui.badge size="sm"
                                        :hex="$pmHex[$slug] ?? TipoPago::getDefaultBadgeHex()"
                                        :icon="$pmIcons[$slug] ?? TipoPago::getDefaultBadgeIcon()">
                                        {{ $payment->tipoPago->nombre ?? 'Pago' }}
                                    </x-ui.badge>
                                    <span class="text-gray-700">{{ $money($payment->amount) }}</span>
                                    @if($payment->reference)
                                        <span class="text-xs text-gray-400">({{ $payment->reference }})</span>
                                    @endif
                                </span>
                            @endforeach
                        </span>
                    @endif
                </x-ui.infolist.entry>

                @if($cashPayment && $sale->cash_received > 0)
                    <x-ui.infolist.entry label="Efectivo recibido" :value="$money($sale->cash_received)" />
                    <x-ui.infolist.entry label="Vuelto" :value="$money($sale->cash_change)" />
                @endif

                @if($isCredit && $sale->receivable)
                    <x-ui.infolist.entry label="Vence" :value="$sale->receivable->due_date?->format('d/m/Y')" />
                    <x-ui.infolist.entry label="Saldo pendiente">
                        <span @class([
                            'font-semibold',
                            'text-amber-600' => $sale->receivable->current_balance > 0,
                            'text-emerald-600' => $sale->receivable->current_balance <= 0,
                        ])>{{ $money($sale->receivable->current_balance) }}</span>
                    </x-ui.infolist.entry>
                @endif
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: productos vendidos --}}
        <x-ui.infolist.section title="Productos vendidos" icon="heroicon-o-queue-list" :cols="0"
            :description="$sale->items->count().' '.($sale->items->count() === 1 ? 'línea' : 'líneas')">
            <x-ui.infolist.repeatable :empty="$sale->items->isEmpty()" emptyIcon="heroicon-o-queue-list" emptyTitle="Sin líneas">
                @foreach($sale->items as $item)
                    @php
                        $taxes = collect($item->tax_breakdown ?? []);
                        $returned = (float) ($returnedByItem[$item->id] ?? 0);
                    @endphp
                    <x-ui.infolist.repeatable-item :cols="7">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                            @if($item->product?->sku)
                                <span class="block text-xs font-normal text-gray-400 font-mono">{{ $item->product->sku }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Cant.">
                            <span class="inline-flex flex-wrap gap-1">
                                <x-ui.badge variant="slate" size="sm" :dot="false">{{ $fmtQty($item->quantity) }}</x-ui.badge>
                                @if($returned > 0)
                                    <x-ui.badge variant="warning" size="sm" :dot="false">{{ $fmtQty($returned) }} devuelta(s)</x-ui.badge>
                                @endif
                            </span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="P. unitario" :value="$money($item->unit_price)" />
                        <x-ui.infolist.entry label="Dto.">
                            @if($item->discount_amount > 0)
                                <span class="text-amber-600">-{{ $money($item->discount_amount) }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Impuestos">
                            <span class="inline-flex flex-wrap gap-1">
                                @forelse($taxes as $tax)
                                    <x-ui.badge variant="info" size="sm" :dot="false">{{ $tax['label'] }}</x-ui.badge>
                                @empty
                                    <x-ui.badge variant="slate" size="sm" :dot="false">Sin impuesto</x-ui.badge>
                                @endforelse
                            </span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Subtotal" class="sm:text-right">
                            <span class="font-semibold text-zertix-primary-700">{{ $money($item->subtotal) }}</span>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>

        {{-- Devoluciones de esta venta (solo si hay) --}}
        @if($sale->returns->isNotEmpty() && auth()->user()->canany(['returns.create', 'returns.void']))
            <x-ui.infolist.section title="Devoluciones" icon="heroicon-o-arrow-uturn-left" :cols="0">
                <x-ui.infolist.repeatable>
                    @foreach($sale->returns as $return)
                        <x-ui.infolist.repeatable-item :cols="5">
                            <x-ui.infolist.entry label="Número" :value="$return->number" strong :href="route('sales.returns.show', $return)" />
                            <x-ui.infolist.entry label="Fecha" :value="$return->created_at->format('d/m/Y h:i A')" />
                            <x-ui.infolist.entry label="Método" :value="$return->refund_method_label" />
                            <x-ui.infolist.entry label="Estado">
                                <x-ui.badge :variant="$return->isVoided() ? 'error' : 'success'" size="sm" :dot="false">
                                    {{ \App\Models\Sales\Returns\SaleReturn::getStatuses()[$return->status] ?? $return->status }}
                                </x-ui.badge>
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Monto" :value="$money($return->refund_value)" class="sm:text-right" />
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>
            </x-ui.infolist.section>
        @endif
    </div>

    {{-- Modal de anular (mismo partial del listado) y modal de devolución --}}
    @include('sales.partials.modals', ['items' => collect([$sale])])
    @can('returns.create')
        <livewire:app.sales.return-form />
    @endcan
</x-app-layout>
