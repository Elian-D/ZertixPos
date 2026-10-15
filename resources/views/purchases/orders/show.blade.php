{{-- Orden de compra — show (v1.5.0 REQ-5.1, /filament-show): datos, líneas con pedido,
     recibido, rechazado y pendiente, y las recepciones de la orden.
     Recibe $order (vendor, warehouse, account, usuarios, items.product, receptions con payable
     y sumas de cantidades). --}}
@use('App\Models\Purchases\PurchaseOrder')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $user = auth()->user();

    $canEdit = $order->isDraft() && $user->can('purchase_orders.create');
    $canApprove = $order->isDraft() && $user->can('purchase_orders.approve');
    $canReceive = $order->canReceive() && $user->can('purchase_receptions.create');
    $canMarkSent = $order->canReceive() && ! $order->sent_at && $user->can('purchase_orders.create');
    $canCancel = $order->canCancel() && $order->receptions->isEmpty() && $user->can('purchase_orders.cancel');

    $pendingUnits = $order->items->sum(fn ($i) => $i->pending);
    $receivedTotal = $order->receptions->sum('total');
    $hasSecondary = $order->isDraft() || $canMarkSent || $canCancel;
    $countLabel = fn ($n, $one, $many) => $n.' '.($n === 1 ? $one : $many);
@endphp

<x-app-layout :title="'Orden de compra '.$order->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Orden de compra '.$order->number"
            :description="($order->vendor?->display_name ?? 'Proveedor').' → '.($order->warehouse->name ?? '')">
            <x-slot:actions>
                <x-ui.button href="{{ route('purchases.orders.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($canEdit)
                    <x-ui.button href="{{ route('purchases.orders.edit', $order) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @else
                    <x-ui.button href="{{ route('purchases.orders.pdf', $order) }}" target="_blank" variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                        PDF
                    </x-ui.button>
                @endif
                @if($canApprove)
                    <x-ui.button variant="primary" iconLeft="heroicon-s-check-badge" x-data x-on:click="$dispatch('open-modal', 'approve-order')">
                        Aprobar
                    </x-ui.button>
                @elseif($canReceive)
                    <x-ui.button href="{{ route('purchases.orders.receive.form', $order) }}" variant="primary" iconLeft="heroicon-s-inbox-arrow-down">
                        Recibir
                    </x-ui.button>
                @endif
            </x-slot:actions>

            @if($hasSecondary)
                <x-slot:secondary>
                    @if($order->isDraft())
                        <x-ui.button href="{{ route('purchases.orders.pdf', $order) }}" target="_blank" variant="secondary" appearance="ghost"
                            class="w-full justify-start" iconLeft="heroicon-s-printer">
                            PDF
                        </x-ui.button>
                    @endif
                    @if($canMarkSent)
                        <x-ui.button variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-paper-airplane"
                            x-data x-on:click="$dispatch('open-modal', 'sent-order')">
                            Marcar como enviada
                        </x-ui.button>
                    @endif
                    @if($canCancel)
                        <x-ui.button variant="error" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-x-circle"
                            x-data x-on:click="$dispatch('open-modal', 'cancel-order')">
                            Cancelar orden
                        </x-ui.button>
                    @endif
                </x-slot:secondary>
            @endif
        </x-ui.page-header>

        @if($order->status === PurchaseOrder::STATUS_CANCELED)
            <x-ui.alert variant="error">
                Cancelada por <strong>{{ $order->canceler->name ?? '—' }}</strong> el {{ $order->canceled_at?->format('d/m/Y h:i A') }}. No movió existencias ni dinero.
            </x-ui.alert>
        @elseif($order->status === PurchaseOrder::STATUS_PARTIAL)
            <x-ui.alert variant="neutral" icon="heroicon-s-inbox-arrow-down">
                Faltan {{ $fmtQty($pendingUnits) }} unidades. Si el proveedor no las va a mandar, recíbelas como rechazadas con su motivo.
            </x-ui.alert>
        @elseif($order->isDraft())
            <x-ui.alert variant="neutral">Borrador: apruébala para poder recibirla. La deuda nace al recibir, no al aprobar.</x-ui.alert>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui.stat label="Total de la orden" :value="$money($order->total)"
                :hint="$countLabel($order->items->count(), 'línea', 'líneas').' · '.$fmtQty($order->items->sum('quantity')).' unidades'" />
            <x-ui.stat label="Recibido" :value="$money($receivedTotal)"
                :hint="$order->receptions->isEmpty() ? 'Sin recepciones' : $countLabel($order->receptions->count(), 'recepción', 'recepciones')" />
            <x-ui.stat label="Por recibir" :value="$fmtQty($pendingUnits).' u.'"
                :hint="$pendingUnits > 0 ? 'Pendiente en las líneas' : 'Nada pendiente'" />
        </div>

        {{-- Datos --}}
        <x-ui.infolist.section title="Datos de la orden" icon="heroicon-o-clipboard-document-list" :cols="3">
            <x-ui.infolist.entry label="Número" :value="$order->number" strong />
            <x-ui.infolist.entry label="Estado">
                <x-ui.badge :variant="$order->status_variant" size="sm" :dot="false">{{ $order->status_label }}</x-ui.badge>
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Proveedor" :value="$order->vendor?->display_name" strong
                :href="$order->vendor && ! $order->vendor->trashed() && $user->can('vendors.view') ? route('purchases.vendors.show', $order->vendor) : null" />
            <x-ui.infolist.entry label="Almacén de destino" :value="$order->warehouse->name ?? null" />
            <x-ui.infolist.entry label="Fecha" :value="$order->order_date->format('d/m/Y')" />
            <x-ui.infolist.entry label="Entrega estimada" :value="$order->expected_date?->format('d/m/Y')" />
            <x-ui.infolist.entry label="Condición"
                :value="$order->condition_label.(! $order->isCash() && $order->vendor?->credit_days ? ' · '.$order->vendor->credit_days.' días' : '')" />
            @if($order->isCash())
                <x-ui.infolist.entry label="Cuenta de pago sugerida" :value="$order->account?->name ?? 'Se elige al recibir'" />
            @endif
            <x-ui.infolist.entry label="Creó" :value="($order->creator->name ?? '—').' · '.$order->created_at->format('d/m/Y h:i A')" />
            @if($order->approved_at)
                <x-ui.infolist.entry label="Aprobó" :value="($order->approver->name ?? '—').' · '.$order->approved_at->format('d/m/Y h:i A')" />
            @endif
            @if($order->sent_at)
                <x-ui.infolist.entry label="Enviada al proveedor" :value="($order->sender->name ?? '—').' · '.$order->sent_at->format('d/m/Y h:i A')" />
            @endif
            <x-ui.infolist.entry label="Nota" :value="$order->notes" full />
        </x-ui.infolist.section>

        {{-- Líneas --}}
        <x-ui.infolist.section title="Productos" icon="heroicon-o-queue-list" :cols="0"
            :description="$countLabel($order->items->count(), 'línea', 'líneas')">
            <x-ui.infolist.repeatable>
                @foreach($order->items as $item)
                    <x-ui.infolist.repeatable-item :cols="6">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                            @if($item->product?->sku)
                                <span class="block text-xs font-normal font-mono text-gray-400">{{ $item->product->sku }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Pedido" strong>
                            {{ $fmtQty($item->quantity) }} × {{ $money($item->unit_cost) }}
                            <span class="block text-xs font-normal text-gray-500">{{ $money($item->subtotal) }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Recibido" :value="$fmtQty($item->quantity_received)" />
                        <x-ui.infolist.entry label="Rechazado">
                            <span @class(['text-state-error font-semibold' => $item->quantity_rejected > 0])>{{ $fmtQty($item->quantity_rejected) }}</span>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Pendiente" class="sm:text-right">
                            @if($item->pending > 0)
                                <x-ui.badge variant="warning" size="sm" :dot="false">Faltan {{ $fmtQty($item->pending) }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="success" size="sm" :dot="false">Completo</x-ui.badge>
                            @endif
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>

        {{-- Recepciones --}}
        @unless($order->isDraft() || $order->status === PurchaseOrder::STATUS_CANCELED)
            <x-ui.infolist.section title="Recepciones" icon="heroicon-o-inbox-arrow-down" :cols="0"
                description="Cada entrega del proveedor. No se editan ni se anulan.">
                <x-ui.infolist.repeatable :empty="$order->receptions->isEmpty()" emptyIcon="heroicon-o-inbox-arrow-down"
                    emptyTitle="Sin recepciones" emptyDescription="Cuando llegue la mercancía, regístrala con Recibir.">
                    @foreach($order->receptions as $reception)
                        <x-ui.infolist.repeatable-item :cols="5">
                            <x-ui.infolist.entry label="Recepción" strong :href="route('purchases.receptions.show', $reception)">
                                {{ $reception->number }}
                                <span class="block text-xs font-normal text-gray-500">{{ $reception->reception_date->format('d/m/Y') }} · {{ $reception->creator->name ?? '—' }}</span>
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Factura" :value="$reception->supplier_invoice ?? $reception->supplier_ncf" />
                            <x-ui.infolist.entry label="Recibido">
                                {{ $fmtQty($reception->quantity_received_total) }} u.
                                @if($reception->quantity_rejected_total > 0)
                                    <span class="block text-xs text-state-error">{{ $fmtQty($reception->quantity_rejected_total) }} rechazadas</span>
                                @endif
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Total" :value="$money($reception->total)" strong />
                            <x-ui.infolist.entry label="Cuenta por pagar" class="sm:text-right">
                                @if($reception->payable)
                                    <x-ui.badge :variant="$reception->payable->status_variant" size="sm" :dot="false">{{ $reception->payable->number }} · {{ $reception->payable->status_label }}</x-ui.badge>
                                @else
                                    <span class="text-xs text-gray-400">Sin deuda</span>
                                @endif
                            </x-ui.infolist.entry>
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>
            </x-ui.infolist.section>
        @endunless
    </div>

    @if($canApprove)
        <x-ui.confirm-modal name="approve-order" title="¿Aprobar orden?" :route="route('purchases.orders.approve', $order)"
            method="PATCH" confirmLabel="Aprobar" icon="heroicon-s-check-badge"
            description="Ya no se podrá editar y se podrá recibir. No mueve existencias ni dinero." />
    @endif

    @if($canMarkSent)
        <x-ui.confirm-modal name="sent-order" title="¿Marcar como enviada?" :route="route('purchases.orders.sent', $order)"
            method="PATCH" confirmLabel="Marcar enviada" icon="heroicon-s-paper-airplane"
            description="Queda registrado que se le mandó al proveedor. No cambia el estado: se puede recibir igual." />
    @endif

    @if($canCancel)
        <x-ui.confirm-modal name="cancel-order" title="¿Cancelar orden?" :route="route('purchases.orders.cancel', $order)"
            method="PATCH" variant="error" confirmLabel="Cancelar orden"
            description="Queda cancelada y ya no se puede recibir. No mueve existencias ni dinero." />
    @endif
</x-app-layout>
