{{-- Recepción de compra — show (v1.5.0 REQ-5.2, /filament-show): de consulta, no se edita
     ni se anula. Datos de la entrega, lo que se debe o se pagó, y lo recibido y rechazado
     por línea. Recibe $reception (order.vendor, order.warehouse, account, creator,
     items.product, items.orderItem, payable.payments.account). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $user = auth()->user();
    $order = $reception->order;
    $payable = $reception->payable;
    $canSeePayable = $payable && $user->can('payables.view');
    $unitsIn = $reception->items->sum('quantity_received');
    $unitsRejected = $reception->items->sum('quantity_rejected');
@endphp

<x-app-layout :title="'Recepción '.$reception->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Recepción '.$reception->number"
            :description="($order->vendor?->display_name ?? 'Proveedor').' → '.($order->warehouse->name ?? '').' · orden '.$order->number">
            <x-slot:actions>
                <x-ui.button href="{{ route('purchases.orders.show', $order) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver a la orden
                </x-ui.button>
                <x-ui.button href="{{ route('purchases.receptions.pdf', $reception) }}" target="_blank" variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    PDF
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui.stat label="Total recibido" :value="$money($reception->total)"
                :hint="$payable ? ($order->isCash() ? 'Pagado de contado' : 'Cuenta por pagar '.$payable->number) : 'Sin deuda: todo se rechazó'" />
            <x-ui.stat label="Entró al inventario" :value="$fmtQty($unitsIn).' u.'" :hint="'En '.($order->warehouse->name ?? 'el almacén')" />
            <x-ui.stat label="Rechazado" :value="$fmtQty($unitsRejected).' u.'" :variant="$unitsRejected > 0 ? 'error' : 'default'"
                :hint="$unitsRejected > 0 ? 'No entró ni se paga' : 'Nada rechazado'" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <x-ui.infolist.section title="Entrega" icon="heroicon-o-truck" :cols="2">
                <x-ui.infolist.entry label="Número" :value="$reception->number" strong />
                <x-ui.infolist.entry label="Fecha" :value="$reception->reception_date->format('d/m/Y')" />
                <x-ui.infolist.entry label="Orden" :value="$order->number" :href="route('purchases.orders.show', $order)" />
                <x-ui.infolist.entry label="Proveedor" :value="$order->vendor?->display_name"
                    :href="$order->vendor && ! $order->vendor->trashed() && $user->can('vendors.view') ? route('purchases.vendors.show', $order->vendor) : null" />
                <x-ui.infolist.entry label="Factura del proveedor" :value="$reception->supplier_invoice" />
                <x-ui.infolist.entry label="NCF del proveedor" :value="$reception->supplier_ncf" />
                <x-ui.infolist.entry label="Calificación">
                    @if($reception->rating)
                        <x-ui.badge :variant="$reception->rating_variant" size="sm" :dot="false">{{ $reception->rating }}</x-ui.badge>
                        @if($reception->rating_note)
                            <span class="block mt-1 text-xs text-gray-500">{{ $reception->rating_note }}</span>
                        @endif
                    @else
                        <span class="text-gray-400">Sin calificar</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Registró" :value="($reception->creator->name ?? '—').' · '.$reception->created_at->format('d/m/Y h:i A')" />
                <x-ui.infolist.entry label="Nota" :value="$reception->notes" full />
            </x-ui.infolist.section>

            <x-ui.infolist.section :title="$order->isCash() ? 'Pago de contado' : 'Cuenta por pagar'" icon="heroicon-o-banknotes" :cols="2">
                @if($payable)
                    <x-ui.infolist.entry label="Cuenta por pagar" :value="$payable->number" strong
                        :href="$canSeePayable ? route('finance.payables.show', $payable) : null" />
                    <x-ui.infolist.entry label="Estado">
                        <x-ui.badge :variant="$payable->status_variant" size="sm" :dot="false">{{ $payable->status_label }}</x-ui.badge>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Total" :value="$money($payable->total_amount)" />
                    <x-ui.infolist.entry label="Pendiente" :value="$money($payable->balance)" />
                    @if($order->isCash())
                        <x-ui.infolist.entry label="Pagado desde" :value="$reception->account?->name" />
                        <x-ui.infolist.entry label="Pago" :value="$payable->payments->first()?->number" />
                    @else
                        <x-ui.infolist.entry label="Vence" :value="$payable->due_date->format('d/m/Y')" />
                        <x-ui.infolist.entry label="Pagos" :value="$payable->payments->where('status', 'active')->count() ?: 'Ninguno'" />
                    @endif
                @else
                    <x-ui.infolist.entry label="Deuda" value="No nació ninguna: todo lo de esta entrega se rechazó." full />
                @endif
            </x-ui.infolist.section>
        </div>

        <x-ui.infolist.section title="Productos" icon="heroicon-o-queue-list" :cols="0"
            :description="$reception->items->count().' '.($reception->items->count() === 1 ? 'línea' : 'líneas')">
            <x-ui.infolist.repeatable>
                @foreach($reception->items as $item)
                    <x-ui.infolist.repeatable-item :cols="5">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                            @if($item->product?->sku)
                                <span class="block text-xs font-normal font-mono text-gray-400">{{ $item->product->sku }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Recibido" strong>
                            {{ $fmtQty($item->quantity_received) }} × {{ $money($item->unit_cost) }}
                            @if($item->orderItem && abs((float) $item->unit_cost - (float) $item->orderItem->unit_cost) > 0.00001)
                                <span class="block text-xs font-normal text-state-warning">Orden: {{ $money($item->orderItem->unit_cost) }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Rechazado">
                            @if($item->quantity_rejected > 0)
                                <span class="font-semibold text-state-error">{{ $fmtQty($item->quantity_rejected) }}</span>
                                <span class="block text-xs text-gray-500">{{ $item->reject_reason_label }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Subtotal" class="sm:text-right" :value="$money($item->subtotal)" strong />
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>
</x-app-layout>
