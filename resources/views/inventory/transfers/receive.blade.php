{{-- Recibir transferencia (v1.5.0 REQ-2.4). Una fila compacta por línea con la cantidad
     recibida (por defecto, la enviada). Lo que falte queda como merma "Pérdida en tránsito".
     Recibe $transfer (fromWarehouse, toWarehouse, sender, items.product con costo). --}}
@php
    $currency = config('regional.currency_symbol');
    $items = $transfer->items->map(fn ($i) => [
        'id' => $i->id,
        'name' => $i->product->name ?? 'Producto eliminado',
        'code' => collect([$i->product?->sku, $i->product?->barcode])->filter()->join(' · '),
        'sent' => (float) $i->quantity_sent,
        'cost' => (float) ($i->product->cost ?? 0),
        'received' => (string) old('received.'.$i->id, (float) $i->quantity_sent),
    ])->values();
@endphp

<x-app-layout :title="'Recibir '.$transfer->number">
    <div class="p-4 md:p-6"
        x-data="{
            items: @js($items),
            currency: @js($currency),
            missing(i) { return Math.max(0, i.sent - (parseFloat(i.received) || 0)); },
            get totalMissing() { return this.items.reduce((s, i) => s + this.missing(i), 0); },
            get lostValue() { return this.items.reduce((s, i) => s + this.missing(i) * i.cost, 0); },
            qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
            money(v) { return this.currency + Number(v).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        }">

        <x-ui.page-header :title="'Recibir '.$transfer->number"
            :description="$transfer->fromWarehouse->name.' → '.$transfer->toWarehouse->name.' · enviada '.$transfer->sent_at?->format('d/m/Y h:i A')">
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.transfers.show', $transfer) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}" class="mt-6 flex flex-col gap-6">
            @csrf

            <x-ui.infolist.section title="Lo que llegó" icon="heroicon-o-inbox-arrow-down" :cols="0"
                description="Confirma lo recibido en cada línea. Si llegó menos, la diferencia queda como merma.">
                <x-ui.infolist.repeater>
                    <template x-for="item in items" :key="item.id">
                        <x-ui.infolist.repeater-item compact>
                            <x-slot:title>
                                <span class="block font-medium truncate" x-text="item.name"></span>
                                <span class="block text-xs text-gray-400 font-mono truncate" x-text="item.code"></span>
                            </x-slot:title>

                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                Enviado <span class="font-semibold text-gray-800 tabular-nums" x-text="qty(item.sent)"></span>
                            </span>

                            <div class="w-28">
                                <x-ui.forms.input name="" type="number" step="0.01" min="0" placeholder="0"
                                    x-bind:name="'received[' + item.id + ']'" x-bind:max="item.sent"
                                    x-model="item.received" x-bind:aria-label="'Cantidad recibida de ' + item.name" />
                            </div>

                            <span class="w-20 text-right text-xs font-semibold tabular-nums"
                                :class="missing(item) > 0 ? 'text-state-error' : 'text-gray-400'"
                                x-text="missing(item) > 0 ? 'Faltan ' + qty(missing(item)) : 'Completo'"></span>
                        </x-ui.infolist.repeater-item>
                    </template>
                </x-ui.infolist.repeater>
            </x-ui.infolist.section>

            <x-ui.alert variant="neutral" x-show="totalMissing > 0" x-cloak>
                <span>Faltan <strong class="tabular-nums" x-text="qty(totalMissing)"></strong> unidades
                    (<span class="tabular-nums" x-text="money(lostValue)"></span> al costo). Quedarán como merma "Pérdida en tránsito".</span>
            </x-ui.alert>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.transfers.show', $transfer) }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Confirmar recepción
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
