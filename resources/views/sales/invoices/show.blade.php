{{-- Detalle de la factura — patrón Infolist (/filament-show, docs/ui/infolist.md)
     con columna lateral: la vista previa del ticket (iframe a finance.invoices.preview).
     Recibe $invoice (sale con items, client, user, payments, receivable, ncfLog cargados). --}}
@use('App\Models\Sales\Invoice')
@use('App\Models\Sales\Sale')
@use('App\Models\Configuration\TipoPago')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $sale = $invoice->sale;
    $ncf = $sale?->ncfLog;
    $isCanceled = $invoice->status === Invoice::STATUS_CANCELLED;
    $cancelReason = $sale?->cancellation_reason ?? $ncf?->cancellation_reason;
    $isCredit = $sale?->payment_type === Sale::PAYMENT_CREDIT;
    $pmHex = TipoPago::getBadgeHexColors();
    $pmIcons = TipoPago::getBadgeIcons();
@endphp

<x-app-layout :title="'Factura '.$invoice->invoice_number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Factura '.$invoice->invoice_number" description="Ver factura">
            <x-slot:actions>
                <x-ui.button href="{{ route('finance.invoices.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button :href="route('finance.invoices.print', ['invoice' => $invoice, 'format' => 'letter', 'download' => 1])"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-down-tray">
                    PDF
                </x-ui.button>
                <x-ui.button :href="route('finance.invoices.print', ['invoice' => $invoice, 'format' => 'ticket'])" target="_blank"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    Imprimir ticket
                </x-ui.button>
            </x-slot:actions>

            <x-slot:secondary>
                <x-ui.button :href="route('finance.invoices.print', ['invoice' => $invoice, 'format' => 'letter'])" target="_blank"
                    appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-s-document-text">
                    Imprimir en carta
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        @if($isCanceled)
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-x-circle class="w-5 h-5 shrink-0" />
                Factura anulada{{ $cancelReason ? ' — '.$cancelReason : '' }}.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 flex flex-col gap-6 min-w-0">

                {{-- Datos de la factura --}}
                <x-ui.infolist.section title="Datos de la factura" icon="heroicon-o-document-text" :cols="3">
                    <x-ui.infolist.entry label="Número" :value="$invoice->invoice_number" strong />
                    <x-ui.infolist.entry label="Estado">
                        <x-ui.badge :variant="$invoice->status_variant" size="sm" :dot="false">{{ $invoice->status_label }}</x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Emitida">
                        {{ $invoice->created_at->format('d/m/Y') }}
                        <span class="block text-xs text-gray-400">{{ $invoice->created_at->format('h:i A') }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Cliente" :value="$sale?->client?->display_name" strong
                        :href="$sale?->client && auth()->user()->can('clients.view') ? route('clients.show', $sale->client) : null" />
                    <x-ui.infolist.entry label="RNC / Cédula" :value="$sale?->client?->tax_id" />
                    <x-ui.infolist.entry label="Venta" :value="$sale?->number"
                        :href="$sale && auth()->user()->can('sales.view') ? route('sales.show', $sale) : null" />
                    <x-ui.infolist.entry label="Vendedor" :value="$sale?->user?->name" />
                    <x-ui.infolist.entry label="Formato" :value="Invoice::getFormats()[$invoice->format_type] ?? $invoice->format_type" />
                    @if($isCredit)
                        <x-ui.infolist.entry label="Vence">
                            @if($invoice->due_date)
                                <span @class(['text-state-error font-medium' => $invoice->due_date->isPast() && ($sale->receivable?->current_balance ?? 0) > 0])>
                                    {{ $invoice->due_date->format('d/m/Y') }}
                                </span>
                            @endif
                        </x-ui.infolist.entry>
                    @endif
                </x-ui.infolist.section>

                {{-- Comprobante fiscal (solo con NCF) --}}
                @if($ncf)
                    <x-ui.infolist.section title="Comprobante fiscal" icon="heroicon-o-shield-check" :cols="3">
                        <x-ui.infolist.entry label="NCF">
                            <span class="font-mono font-semibold text-gray-900">{{ $ncf->full_ncf }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Tipo" :value="$ncf->type ? $ncf->type->code.' — '.$ncf->type->name : null" />
                        <x-ui.infolist.entry label="Válido hasta" :value="$ncf->sequence?->expiry_date?->format('d/m/Y')" />
                    </x-ui.infolist.section>
                @endif

                {{-- Totales y pago --}}
                <x-ui.infolist.section title="Totales y pago" icon="heroicon-o-banknotes">
                    <x-ui.infolist.entry label="Subtotal" :value="$money($sale?->total_amount)" />
                    <x-ui.infolist.entry label="Descuento">
                        <span @class(['text-amber-600' => ($sale?->discount_total ?? 0) > 0])>
                            {{ ($sale?->discount_total ?? 0) > 0 ? '-' : '' }}{{ $money($sale?->discount_total) }}
                        </span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Impuestos" :value="$money($sale?->tax_amount)" />
                    <x-ui.infolist.entry label="Total">
                        <span class="text-xl font-bold text-zertix-primary-700">{{ $money($sale?->grand_total) }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Tipo de venta">
                        <x-ui.badge :variant="$isCredit ? 'warning' : 'info'" size="sm" :dot="false">{{ $isCredit ? 'Crédito' : 'Contado' }}</x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Método de pago" class="sm:col-span-2">
                        @if($sale && $sale->payments->isNotEmpty())
                            <span class="inline-flex flex-wrap gap-2">
                                @foreach($sale->payments as $payment)
                                    @php $slug = $payment->tipoPago?->slug; @endphp
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-ui.badge size="sm" :hex="$pmHex[$slug] ?? TipoPago::getDefaultBadgeHex()" :icon="$pmIcons[$slug] ?? TipoPago::getDefaultBadgeIcon()">
                                            {{ $payment->tipoPago->nombre ?? 'Pago' }}
                                        </x-ui.badge>
                                        <span class="text-gray-700">{{ $money($payment->amount) }}</span>
                                    </span>
                                @endforeach
                            </span>
                        @endif
                    </x-ui.infolist.entry>
                    @if($isCredit && $sale->receivable)
                        <x-ui.infolist.entry label="Saldo pendiente">
                            <span @class([
                                'font-semibold',
                                'text-amber-600' => $sale->receivable->current_balance > 0,
                                'text-emerald-600' => $sale->receivable->current_balance <= 0,
                            ])>{{ $money($sale->receivable->current_balance) }}</span>
                            @can('receivables.view')
                                <a href="{{ route('finance.receivables.show', $sale->receivable) }}" class="block text-xs text-zertix-primary-700 hover:underline">Ver cuenta por cobrar</a>
                            @endcan
                        </x-ui.infolist.entry>
                    @endif
                </x-ui.infolist.section>

                {{-- Productos facturados --}}
                <x-ui.infolist.section title="Productos facturados" icon="heroicon-o-queue-list" :cols="0"
                    :description="($sale?->items->count() ?? 0).' '.(($sale?->items->count() ?? 0) === 1 ? 'línea' : 'líneas')">
                    <x-ui.infolist.repeatable :empty="! $sale || $sale->items->isEmpty()" emptyIcon="heroicon-o-queue-list" emptyTitle="Sin líneas">
                        @foreach($sale?->items ?? [] as $item)
                            @php $taxes = collect($item->tax_breakdown ?? []); @endphp
                            <x-ui.infolist.repeatable-item :cols="7">
                                <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                                    {{ $item->product->name ?? 'Producto eliminado' }}
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Cant.">
                                    <x-ui.badge variant="slate" size="sm" :dot="false">{{ $fmtQty($item->quantity) }}</x-ui.badge>
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
            </div>

            {{-- Columna lateral: vista previa del ticket --}}
            <x-ui.infolist.section title="Comprobante" icon="heroicon-o-printer" :cols="0" class="lg:sticky lg:top-6">
                <div class="-m-5 sm:-m-6 bg-slate-100 p-4 flex justify-center rounded-b-2xl">
                    <iframe src="{{ route('finance.invoices.preview', ['invoice' => $invoice, 'format' => 'ticket']) }}"
                            title="Factura {{ $invoice->invoice_number }}"
                            class="w-full max-w-[320px] h-[620px] bg-white rounded shadow"></iframe>
                </div>
            </x-ui.infolist.section>
        </div>
    </div>
</x-app-layout>
