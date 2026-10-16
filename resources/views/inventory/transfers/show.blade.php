{{-- Transferencia — show (v1.5.0 REQ-2.4, /filament-show): enviado y recibido por línea.
     Recibe $transfer (almacenes, usuarios, items.product, waste) y $originStock
     (existencia actual en el origen, solo en borrador). --}}
@use('App\Models\Inventory\InventoryTransfer')
@php
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $user = auth()->user();
    $canEdit = $transfer->isDraft() && $user->can('inventory_transfers.create');
    $canSend = $transfer->isDraft() && $user->can('inventory_transfers.send');
    $canReceive = $transfer->isSent() && $user->can('inventory_transfers.receive');
    $short = $transfer->items->filter(fn ($i) => $transfer->isDraft() && (float) $i->quantity_sent > (float) ($originStock[$i->product_id] ?? 0));
@endphp

<x-app-layout :title="'Transferencia '.$transfer->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Transferencia '.$transfer->number"
            :description="$transfer->fromWarehouse->name.' → '.$transfer->toWarehouse->name">
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.transfers.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($canEdit)
                    <x-ui.button href="{{ route('inventory.transfers.edit', $transfer) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @endif
                @if($canSend)
                    <x-ui.button variant="primary" iconLeft="heroicon-s-paper-airplane" x-data x-on:click="$dispatch('open-modal', 'send-transfer')">
                        Enviar
                    </x-ui.button>
                @elseif($canReceive)
                    <x-ui.button href="{{ route('inventory.transfers.receive.form', $transfer) }}" variant="primary" iconLeft="heroicon-s-inbox-arrow-down">
                        Recibir
                    </x-ui.button>
                @endif
                @unless($canEdit)
                    <x-ui.button href="{{ route('inventory.transfers.pdf', $transfer) }}" target="_blank" variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                        PDF
                    </x-ui.button>
                @endunless
            </x-slot:actions>

            {{-- Solo en borrador hay más de tres acciones: PDF y Cancelar van al menú. --}}
            @if($canEdit)
                <x-slot:secondary>
                    <x-ui.button href="{{ route('inventory.transfers.pdf', $transfer) }}" target="_blank" variant="secondary" appearance="ghost"
                        class="w-full justify-start" iconLeft="heroicon-s-printer">
                        PDF
                    </x-ui.button>
                    <x-ui.button variant="error" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-x-circle"
                        x-data x-on:click="$dispatch('open-modal', 'cancel-transfer')">
                        Cancelar transferencia
                    </x-ui.button>
                </x-slot:secondary>
            @endif
        </x-ui.page-header>

        @if($transfer->status === InventoryTransfer::STATUS_CANCELED)
            <x-ui.alert variant="error">
                Cancelada por <strong>{{ $transfer->canceler->name ?? '—' }}</strong> el {{ $transfer->canceled_at?->format('d/m/Y h:i A') }}. No movió existencias.
            </x-ui.alert>
        @elseif($transfer->isSent())
            <x-ui.alert variant="neutral" icon="heroicon-s-truck">
                En tránsito desde el {{ $transfer->sent_at?->format('d/m/Y h:i A') }}: ya salió de {{ $transfer->fromWarehouse->name }} y entra a {{ $transfer->toWarehouse->name }} al recibirla.
            </x-ui.alert>
        @elseif($transfer->isDraft() && $short->isNotEmpty())
            <x-ui.alert variant="warning">
                No alcanza la existencia en el origen para: {{ $short->map(fn ($i) => $i->product->name)->join(', ') }}. Ajusta las cantidades antes de enviar.
            </x-ui.alert>
        @endif

        {{-- Datos --}}
        <x-ui.infolist.section title="Datos de la transferencia" icon="heroicon-o-arrows-right-left" :cols="3">
            <x-ui.infolist.entry label="Número" :value="$transfer->number" strong />
            <x-ui.infolist.entry label="Estado">
                <x-ui.badge :variant="$transfer->status_variant" size="sm" :dot="false">{{ $transfer->status_label }}</x-ui.badge>
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Creó" :value="($transfer->creator->name ?? '—').' · '.$transfer->created_at->format('d/m/Y h:i A')" />
            <x-ui.infolist.entry label="Desde" :value="$transfer->fromWarehouse->name" strong />
            <x-ui.infolist.entry label="Hacia" :value="$transfer->toWarehouse->name" strong />
            @if($transfer->sent_at)
                <x-ui.infolist.entry label="Envió" :value="($transfer->sender->name ?? '—').' · '.$transfer->sent_at->format('d/m/Y h:i A')" />
            @endif
            @if($transfer->received_at)
                <x-ui.infolist.entry label="Recibió" :value="($transfer->receiver->name ?? '—').' · '.$transfer->received_at->format('d/m/Y h:i A')" />
            @endif
            @if($transfer->waste)
                <x-ui.infolist.entry label="Pérdida en tránsito" :value="$transfer->waste->number"
                    :href="$user->can('inventory_wastes.view') ? route('inventory.wastes.show', $transfer->waste) : null" />
            @endif
            <x-ui.infolist.entry label="Nota" :value="$transfer->notes" full />
        </x-ui.infolist.section>

        {{-- Líneas: enviado y recibido --}}
        <x-ui.infolist.section title="Productos" icon="heroicon-o-queue-list" :cols="0"
            :description="$transfer->items->count().' '.($transfer->items->count() === 1 ? 'línea' : 'líneas')">
            <x-ui.infolist.repeatable>
                @foreach($transfer->items as $item)
                    <x-ui.infolist.repeatable-item :cols="5">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                            @if($item->notes)
                                <span class="block text-xs font-normal text-gray-500">{{ $item->notes }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Enviado" :value="$fmtQty($item->quantity_sent)" strong />
                        @if($transfer->isDraft())
                            <x-ui.infolist.entry label="Existencia en origen">
                                <span @class(['font-semibold', 'text-state-error' => $short->contains('id', $item->id)])>{{ $fmtQty($originStock[$item->product_id] ?? 0) }}</span>
                            </x-ui.infolist.entry>
                        @else
                            <x-ui.infolist.entry label="Recibido" :value="$item->quantity_received !== null ? $fmtQty($item->quantity_received) : null" />
                        @endif
                        <x-ui.infolist.entry label="Diferencia" class="sm:text-right">
                            @if($item->shortage > 0)
                                <x-ui.badge variant="error" size="sm" :dot="false">Faltan {{ $fmtQty($item->shortage) }}</x-ui.badge>
                            @elseif($item->quantity_received !== null)
                                <x-ui.badge variant="success" size="sm" :dot="false">Completo</x-ui.badge>
                            @endif
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>

    @if($canSend)
        <x-ui.confirm-modal name="send-transfer" title="¿Enviar transferencia?" :route="route('inventory.transfers.send', $transfer)"
            confirmLabel="Enviar" icon="heroicon-s-paper-airplane"
            description="La mercancía sale de {{ $transfer->fromWarehouse->name }} y queda en tránsito hasta que se reciba. Ya no se podrá editar." />
    @endif

    @if($canEdit)
        <x-ui.confirm-modal name="cancel-transfer" title="¿Cancelar transferencia?" :route="route('inventory.transfers.cancel', $transfer)"
            method="PATCH" variant="error" confirmLabel="Cancelar transferencia"
            description="Queda cancelada y no mueve existencias." />
    @endif
</x-app-layout>
