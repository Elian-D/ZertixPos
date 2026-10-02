{{-- Detalle de la devolución — patrón Infolist (/filament-show, docs/ui/infolist.md)
     con columna lateral: la vista previa del ticket (iframe en modo preview). --}}
@use('App\Models\Sales\Returns\SaleReturn')
@use('App\Models\Configuration\TipoPago')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $sale = $return->sale;
    $exchangeSale = $return->exchangeSale;
    $methodVariant = match ($return->refund_method) {
        SaleReturn::METHOD_CASH => 'info',
        SaleReturn::METHOD_EXCHANGE => 'primary',
        default => 'warning',
    };
@endphp

<x-app-layout :title="'Devolución '.$return->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Devolución '.$return->number" description="Ver devolución">
            <x-slot:actions>
                <x-ui.button href="{{ route('sales.returns.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button href="{{ route('sales.returns.print', ['return' => $return, 'download' => 1]) }}"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-down-tray">
                    PDF
                </x-ui.button>
                <x-ui.button href="{{ route('sales.returns.print', $return) }}" target="_blank"
                    variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    Imprimir ticket
                </x-ui.button>
                @can('returns.void')
                    @unless($return->isVoided())
                        <x-ui.button variant="error" iconLeft="heroicon-s-x-circle"
                            x-data @click="$dispatch('open-modal', 'confirm-deletion-return-{{ $return->id }}')">
                            Anular
                        </x-ui.button>
                    @endunless
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        @if($return->isVoided())
            <div class="rounded-xl border border-state-error/20 bg-state-error/5 px-5 py-3 flex items-center gap-2 text-sm text-state-error">
                <x-heroicon-s-x-circle class="w-5 h-5 shrink-0" />
                Anulada por <strong>{{ $return->voidedBy->name ?? 'N/A' }}</strong> el {{ $return->voided_at?->format('d/m/Y h:i A') }}.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 flex flex-col gap-6 min-w-0">

                {{-- Datos de la devolución --}}
                <x-ui.infolist.section title="Datos de la devolución" icon="heroicon-o-arrow-uturn-left" :cols="3">
                    <x-ui.infolist.entry label="Número" :value="$return->number" strong />
                    <x-ui.infolist.entry label="Estado">
                        <x-ui.badge :variant="$return->isVoided() ? 'error' : 'success'" size="sm" :dot="false">
                            {{ SaleReturn::getStatuses()[$return->status] ?? $return->status }}
                        </x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Fecha y hora" :value="$return->created_at->format('d/m/Y h:i A')" />
                    <x-ui.infolist.entry label="Venta origen" :value="$sale->number" strong
                        :href="auth()->user()->can('sales.view') ? route('sales.show', $sale) : null" />
                    <x-ui.infolist.entry label="Cliente" :value="$sale->client?->display_name"
                        :href="$sale->client && auth()->user()->can('clients.view') ? route('clients.show', $sale->client) : null" />
                    <x-ui.infolist.entry label="Realizada por" :value="$return->user?->name" />
                    <x-ui.infolist.entry label="Método">
                        <x-ui.badge :variant="$methodVariant" size="sm" :dot="false">{{ $return->refund_method_label }}</x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Motivo" :value="$return->reason_label" />
                    <x-ui.infolist.entry label="Observaciones" :value="$return->notes" full />
                </x-ui.infolist.section>

                {{-- Productos devueltos --}}
                <x-ui.infolist.section title="Productos devueltos" icon="heroicon-o-queue-list" :cols="0"
                    :description="$return->items->count().' '.($return->items->count() === 1 ? 'línea' : 'líneas').' · total '.$money($return->refund_value)">
                    <x-ui.infolist.repeatable>
                        @foreach($return->items as $item)
                            @php $product = $item->saleItem->product; @endphp
                            <x-ui.infolist.repeatable-item :cols="7">
                                <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                                    {{ $product->name ?? 'Producto eliminado' }}
                                    @if($product?->sku)
                                        <span class="block text-xs font-normal text-gray-400 font-mono">{{ $product->sku }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Cant.">
                                    <x-ui.badge variant="slate" size="sm" :dot="false">{{ $fmtQty($item->quantity) }}</x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Neto unit." :value="$money($item->unit_subtotal)" />
                                <x-ui.infolist.entry label="ITBIS unit." :value="$money($item->unit_tax)" />
                                <x-ui.infolist.entry label="Inventario">
                                    @if($product?->isService())
                                        <x-ui.badge variant="slate" size="sm" :dot="false">Servicio</x-ui.badge>
                                    @elseif($item->restock)
                                        <x-ui.badge variant="success" size="sm" :dot="false">Regresó</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning" size="sm" :dot="false">No regresó</x-ui.badge>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Total" class="sm:text-right">
                                    <span class="font-semibold text-zertix-primary-700">{{ $money($item->total) }}</span>
                                </x-ui.infolist.entry>
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>
                </x-ui.infolist.section>

                {{-- Resultado --}}
                <x-ui.infolist.section title="Resultado" icon="heroicon-o-banknotes" :cols="2">
                    <x-ui.infolist.entry label="Valor devuelto">
                        <span class="text-xl font-bold text-zertix-primary-700">{{ $money($return->refund_value) }}</span>
                    </x-ui.infolist.entry>

                    @switch($return->refund_method)
                        @case(SaleReturn::METHOD_CASH)
                            <x-ui.infolist.entry label="Efectivo entregado" :value="$money($return->cash_amount)" strong />
                            @break

                        @case(SaleReturn::METHOD_RECEIVABLE)
                            <x-ui.infolist.entry label="Deuda reducida en" :value="$money($return->refund_value)" strong />
                            @break

                        @default
                            @if($exchangeSale)
                                @php
                                    $newLine = $exchangeSale->items->first();
                                    $cashIn = (float) $exchangeSale->payments->filter(fn ($p) => $p->tipoPago?->slug === TipoPago::EFECTIVO)->sum('amount');
                                @endphp
                                <x-ui.infolist.entry label="Reemplazo entregado"
                                    :value="($newLine->product->name ?? 'N/A').' × '.$fmtQty($newLine->quantity)" strong />
                                <x-ui.infolist.entry label="Venta del cambio" :value="$exchangeSale->number"
                                    :href="auth()->user()->can('sales.view') ? route('sales.show', $exchangeSale) : null" />
                                <x-ui.infolist.entry label="Diferencia cobrada" :value="$cashIn > 0 ? $money($cashIn) : null" />
                                <x-ui.infolist.entry label="Diferencia entregada" :value="$return->cash_amount > 0 ? $money($return->cash_amount) : null" />
                            @else
                                <x-ui.infolist.entry label="Cambio" value="Otra unidad del mismo producto, sin costo" />
                            @endif
                    @endswitch
                </x-ui.infolist.section>
            </div>

            {{-- Columna lateral: vista previa del ticket --}}
            <x-ui.infolist.section title="Ticket" icon="heroicon-o-printer" :cols="0" class="lg:sticky lg:top-6">
                <div class="-m-5 sm:-m-6 bg-slate-100 p-4 flex justify-center rounded-b-2xl">
                    <iframe src="{{ route('sales.returns.print', ['return' => $return, 'preview' => 1]) }}"
                            title="Ticket {{ $return->number }}"
                            class="w-full max-w-[320px] h-[520px] bg-white rounded shadow"></iframe>
                </div>
            </x-ui.infolist.section>
        </div>
    </div>

    @can('returns.void')
        @unless($return->isVoided())
            <x-ui.confirm-deletion-modal
                :id="'return-'.$return->id"
                title="¿Anular devolución?"
                :itemName="$return->number"
                type="la devolución"
                method="PATCH"
                :route="route('sales.returns.void', $return)"
                description="Se revierte el inventario y, si aplica, la deuda del cliente. El efectivo entregado se corrige a mano." />
        @endunless
    @endcan
</x-app-layout>
