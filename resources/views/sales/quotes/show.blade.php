{{-- Detalle de la cotización — patrón Infolist de una columna (/filament-show,
     docs/ui/infolist.md). Recibe $quote (items.product, customer, user, sale,
     terminal cargados) y los catálogos del modal de convertir ($tipo_pagos,
     $ncf_types, $warehouses). --}}
@use('App\Models\Sales\Quotes\Quote')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $number = $quote->number;
    $isOpen = in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_APPROVED], true);
    $isExpired = $quote->expires_at?->isPast();
    // Mismas reglas que las acciones de fila del listado (quote-table).
    $actionable = ! $quote->sale_id && ! $isExpired;
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp

<x-app-layout :title="'Cotización '.$number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Cotización '.$number" description="Ver cotización">
            <x-slot:actions>
                <x-ui.button href="{{ route('clients.quotes.index') }}"
                    appearance="outline" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al listado
                </x-ui.button>

                @if($actionable)
                    @can('quotes.convert')
                        @if($quote->status === Quote::STATUS_DRAFT)
                            <x-ui.button variant="success" iconLeft="heroicon-s-check-circle"
                                x-data @click="$dispatch('open-modal', 'confirm-approve-quote-{{ $quote->id }}')">
                                Aprobar
                            </x-ui.button>
                        @elseif($quote->status === Quote::STATUS_APPROVED)
                            <x-ui.button variant="success" iconLeft="heroicon-s-shopping-cart"
                                x-data @click="$dispatch('open-modal', 'confirm-convert-quote-{{ $quote->id }}')">
                                Convertir a venta
                            </x-ui.button>
                        @endif
                    @endcan
                    @can('quotes.cancel')
                        @if($quote->status !== Quote::STATUS_CANCELLED)
                            <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-x-circle"
                                x-data @click="$dispatch('open-modal', 'confirm-cancel-quote-{{ $quote->id }}')">
                                Cancelar
                            </x-ui.button>
                        @endif
                    @endcan
                    @can('quotes.edit')
                        @if($quote->status === Quote::STATUS_DRAFT)
                            <x-ui.button href="{{ route('clients.quotes.edit', $quote) }}" variant="primary" iconLeft="heroicon-s-pencil-square">
                                Editar
                            </x-ui.button>
                        @endif
                    @endcan
                @endif
            </x-slot:actions>

            <x-slot:secondary>
                <x-ui.button href="{{ route('clients.quotes.print', ['quote' => $quote, 'format' => 'ticket']) }}" target="_blank"
                    appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-s-receipt-percent">
                    Imprimir ticket
                </x-ui.button>
                <x-ui.button href="{{ route('clients.quotes.print', ['quote' => $quote, 'format' => 'a4']) }}" target="_blank"
                    appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-s-printer">
                    Imprimir PDF
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        @if($isExpired && $isOpen)
            <div class="rounded-xl border border-state-warning/30 bg-state-warning/10 px-5 py-3 flex items-center gap-2 text-sm text-amber-700">
                <x-heroicon-s-clock class="w-5 h-5 shrink-0" />
                Venció el {{ $quote->expires_at->format('d/m/Y') }} — ya no se puede aprobar, editar ni convertir.
            </div>
        @endif

        {{-- Encabezado de la cotización --}}
        <x-ui.infolist.section title="Encabezado de la cotización" icon="heroicon-o-document-text" :cols="3">
            <x-ui.infolist.entry label="Número" :value="$number" strong />
            <x-ui.infolist.entry label="Estado">
                <x-ui.badge :variant="$quote->status_variant" size="sm" :dot="false">{{ $quote->status_label }}</x-ui.badge>
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Cliente" :value="$quote->customer?->display_name" strong
                :href="$quote->customer && auth()->user()->can('clients.view') ? route('clients.show', $quote->customer) : null" />
            <x-ui.infolist.entry label="Creada" :value="$quote->created_at->format('d/m/Y h:i A')" />
            <x-ui.infolist.entry label="Creada por" :value="$quote->user?->name" />
            <x-ui.infolist.entry label="Válida hasta">
                @if($quote->expires_at)
                    <span @class(['text-state-error font-medium' => $isExpired && $isOpen])>{{ $quote->expires_at->format('d/m/Y') }}</span>
                @endif
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Origen"
                :value="(Quote::getOrigins()[$quote->origin] ?? $quote->origin).($quote->terminal ? ' · '.$quote->terminal->name : '')" />
            @if($quote->sale)
                <x-ui.infolist.entry label="Venta generada" :value="$quote->sale->number" />
            @endif
        </x-ui.infolist.section>

        {{-- Líneas --}}
        <x-ui.infolist.section title="Líneas de la cotización" icon="heroicon-o-queue-list" :cols="0"
            :description="$quote->items->count().' '.($quote->items->count() === 1 ? 'producto' : 'productos')">
            <x-ui.infolist.repeatable :empty="$quote->items->isEmpty()" emptyIcon="heroicon-o-queue-list" emptyTitle="Sin líneas">
                @foreach($quote->items as $item)
                    @php $taxes = collect($item->tax_breakdown ?? []); @endphp
                    <x-ui.infolist.repeatable-item :cols="7">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Cant.">
                            <x-ui.badge variant="slate" size="sm" :dot="false">{{ $fmtQty($item->quantity) }}</x-ui.badge>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="P. unitario" :value="$money($item->price)" />
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

        {{-- Totales --}}
        <x-ui.infolist.section title="Totales" icon="heroicon-o-calculator">
            <x-ui.infolist.entry label="Subtotal" :value="$money($quote->subtotal)" />
            <x-ui.infolist.entry label="Descuento">
                <span @class(['text-amber-600' => $quote->discount_total > 0])>
                    {{ $quote->discount_total > 0 ? '-' : '' }}{{ $money($quote->discount_total) }}
                </span>
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Impuestos" :value="$money($quote->tax_amount)" />
            <x-ui.infolist.entry label="Total">
                <span class="text-xl font-bold text-zertix-primary-700">{{ $money($quote->grand_total) }}</span>
            </x-ui.infolist.entry>
        </x-ui.infolist.section>

        {{-- Notas --}}
        <x-ui.infolist.section title="Notas" icon="heroicon-o-chat-bubble-left-ellipsis" :cols="0" collapsible collapsed>
            @if($quote->notes)
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $quote->notes }}</p>
            @else
                <p class="text-sm text-gray-400">Sin notas.</p>
            @endif
        </x-ui.infolist.section>
    </div>

    @include('sales.quotes.partials.actions-modals', ['items' => collect([$quote])])
</x-app-layout>
