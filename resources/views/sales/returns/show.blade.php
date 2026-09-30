@use('App\Models\Sales\Returns\SaleReturn')
@php
    $currency = config('regional.currency_symbol');
    $sale = $return->sale;
    $exchangeSale = $return->exchangeSale;
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp

<x-app-layout title="Devolución {{ $return->number }}">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Devolución '.$return->number" description="Detalle de la devolución y su resultado.">
            <x-slot:actions>
                <x-ui.button href="{{ route('sales.returns.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button href="{{ route('sales.returns.print', ['return' => $return, 'download' => 1]) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-down-tray">
                    Descargar PDF
                </x-ui.button>
                <x-ui.button href="{{ route('sales.returns.print', $return) }}" target="_blank" variant="primary" iconLeft="heroicon-s-printer">
                    Imprimir ticket
                </x-ui.button>
                @can('returns.void')
                    @unless($return->isVoided())
                        <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-x-circle"
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

        {{-- Cabecera --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 md:p-6 grid grid-cols-2 md:grid-cols-4 gap-5">
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Estado</span>
                <x-ui.badge :variant="$return->isVoided() ? 'error' : 'success'" size="sm" class="mt-1">
                    {{ SaleReturn::getStatuses()[$return->status] ?? $return->status }}
                </x-ui.badge>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Fecha</span>
                <p class="text-sm font-semibold text-slate-800">{{ $return->created_at->format('d/m/Y h:i A') }}</p>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Realizada por</span>
                <p class="text-sm font-semibold text-slate-800">{{ $return->user->name ?? 'N/A' }}</p>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Método</span>
                <p class="text-sm font-semibold text-slate-800">{{ $return->refund_method_label }}</p>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Venta origen</span>
                <p class="text-sm font-mono font-bold text-zertix-primary-700">{{ $sale->number }}</p>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Cliente</span>
                <p class="text-sm font-semibold text-slate-800">{{ $sale->client->name ?? 'Consumidor Final' }}</p>
            </div>
            <div class="col-span-2">
                <span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Motivo</span>
                <p class="text-sm font-semibold text-slate-800">{{ $return->reason_label }}</p>
            </div>
            @if($return->notes)
                <div class="col-span-2 md:col-span-4 bg-amber-50 p-3 rounded-lg border border-dashed border-amber-200">
                    <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-1">Observaciones</span>
                    <p class="text-xs text-amber-800 italic">"{{ $return->notes }}"</p>
                </div>
            @endif
        </div>

        {{-- Línea devuelta --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">Productos devueltos</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-3 text-[10px] font-black uppercase text-slate-400">Producto</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase text-slate-400 text-center">Cant.</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase text-slate-400 text-right">Neto unit.</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase text-slate-400 text-right">ITBIS unit.</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase text-slate-400 text-right">Total</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase text-slate-400 text-center">Inventario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($return->items as $item)
                            @php $product = $item->saleItem->product; @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $product->name ?? 'Producto eliminado' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $product->sku ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-slate-700">{{ $fmtQty($item->quantity) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-600">{{ $currency }}{{ number_format($item->unit_subtotal, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-600">{{ $currency }}{{ number_format($item->unit_tax, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">{{ $currency }}{{ number_format($item->total, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($product?->isService())
                                        <x-ui.badge variant="slate" size="sm" :dot="false">Servicio</x-ui.badge>
                                    @elseif($item->restock)
                                        <x-ui.badge variant="success" size="sm">Regresó</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning" size="sm">No regresó (dañado)</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t bg-slate-50">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-[10px] font-black uppercase text-slate-500">Total devuelto</td>
                            <td class="px-4 py-3 text-right font-mono font-black text-slate-900">{{ $currency }}{{ number_format($return->refund_value, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Resultado --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 md:p-6">
            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Resultado</span>

            @switch($return->refund_method)
                @case(SaleReturn::METHOD_CASH)
                    <p class="text-sm text-slate-700">Efectivo entregado:
                        <span class="font-mono font-black text-lg text-slate-900">{{ $currency }}{{ number_format($return->cash_amount, 2) }}</span>
                    </p>
                    @break

                @case(SaleReturn::METHOD_RECEIVABLE)
                    <p class="text-sm text-slate-700">Deuda del cliente reducida en
                        <span class="font-mono font-black text-lg text-slate-900">{{ $currency }}{{ number_format($return->refund_value, 2) }}</span>
                    </p>
                    @break

                @default
                    @if($exchangeSale)
                        @php
                            $newLine = $exchangeSale->items->first();
                            $cashIn = (float) $exchangeSale->payments->filter(fn ($p) => $p->tipoPago?->slug === \App\Models\Configuration\TipoPago::EFECTIVO)->sum('amount');
                        @endphp
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="block text-[10px] text-slate-500">Reemplazo entregado</span>
                                <span class="font-semibold text-slate-800">{{ $newLine->product->name ?? 'N/A' }} × {{ $fmtQty($newLine->quantity) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-500">Venta del cambio</span>
                                <span class="font-mono font-bold text-zertix-primary-700">{{ $exchangeSale->number }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-500">Diferencia cobrada</span>
                                <span class="font-mono font-bold text-slate-900">{{ $currency }}{{ number_format($cashIn, 2) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-500">Diferencia entregada</span>
                                <span class="font-mono font-bold text-slate-900">{{ $currency }}{{ number_format((float) $return->cash_amount, 2) }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-700">Cambio por otra unidad de cada producto devuelto, sin costo.</p>
                    @endif
            @endswitch
        </div>
        </div>

        {{-- Vista previa del ticket --}}
        <aside class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden lg:sticky lg:top-6">
            <div class="px-5 py-3 border-b bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">Ticket</div>
            <div class="bg-slate-100 p-4 flex justify-center">
                <iframe src="{{ route('sales.returns.print', ['return' => $return, 'preview' => 1]) }}"
                        title="Ticket {{ $return->number }}"
                        class="w-full max-w-[320px] h-[520px] bg-white rounded shadow"></iframe>
            </div>
        </aside>
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
