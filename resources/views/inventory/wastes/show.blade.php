{{-- Merma — show (v1.5.0 REQ-2.2, /filament-show). Recibe $waste (warehouse, creator,
     voider, reference, items.product). --}}
@use('App\Models\Inventory\InventoryWasteItem')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $byReason = $waste->items->groupBy('reason')->map(fn ($items) => (float) $items->sum('total_value'))->sortDesc();
    // Una merma que viene de otro documento (devolución, transferencia) no se anula sola.
    $origin = $waste->origin;
    $canVoid = $waste->isApplied() && ! $waste->reference_type && auth()->user()->can('inventory_wastes.void');
@endphp

<x-app-layout :title="'Merma '.$waste->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Merma '.$waste->number" :description="$waste->warehouse->name.' · '.$waste->waste_date->format('d/m/Y')">
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.wastes.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button href="{{ route('inventory.wastes.pdf', $waste) }}" target="_blank" variant="secondary" appearance="outline" iconLeft="heroicon-s-printer">
                    PDF
                </x-ui.button>
                {{-- Solo tres acciones: Anular va a la vista, sin menú secundario. --}}
                @if($canVoid)
                    <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-x-circle"
                        x-data x-on:click="$dispatch('open-modal', 'void-waste')">
                        Anular
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if($waste->isApplied() && $origin)
            <x-ui.alert variant="neutral">{{ $origin['note'] }}</x-ui.alert>
        @endif

        @if($waste->isVoided())
            <x-ui.alert variant="error">
                Anulada por <strong>{{ $waste->voider->name ?? '—' }}</strong> el {{ $waste->voided_at?->format('d/m/Y h:i A') }}: {{ $waste->void_reason }}. Las existencias regresaron al almacén.
            </x-ui.alert>
        @endif

        {{-- Datos --}}
        <x-ui.infolist.section title="Datos de la merma" icon="heroicon-o-archive-box-x-mark" :cols="3">
            <x-ui.infolist.entry label="Número" :value="$waste->number" strong />
            <x-ui.infolist.entry label="Estado">
                <x-ui.badge :variant="$waste->status_variant" size="sm" :dot="false">{{ $waste->status_label }}</x-ui.badge>
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Fecha" :value="$waste->waste_date->format('d/m/Y')" />
            <x-ui.infolist.entry label="Almacén" :value="$waste->warehouse->name" strong />
            <x-ui.infolist.entry label="Registró" :value="($waste->creator->name ?? '—').' · '.$waste->created_at->format('d/m/Y h:i A')" />
            @if($origin)
                <x-ui.infolist.entry label="Origen" :value="$origin['label']" :href="$origin['url']" />
            @endif
            <x-ui.infolist.entry label="Nota" :value="$waste->notes" full />
        </x-ui.infolist.section>

        {{-- Valor perdido --}}
        <x-ui.infolist.section title="Valor perdido" icon="heroicon-o-banknotes" :cols="4"
            description="Valor al costo de la mercancía dada de baja; no es dinero de caja.">
            <x-ui.infolist.entry label="Total">
                <span @class(['text-xl font-bold', 'text-state-error' => $waste->isApplied(), 'text-gray-400 line-through' => $waste->isVoided()])>{{ $money($waste->total_value) }}</span>
            </x-ui.infolist.entry>
            @foreach($byReason as $reason => $value)
                <x-ui.infolist.entry :label="InventoryWasteItem::getReasons()[$reason] ?? $reason" :value="$money($value)" />
            @endforeach
        </x-ui.infolist.section>

        {{-- Líneas --}}
        <x-ui.infolist.section title="Productos" icon="heroicon-o-queue-list" :cols="0"
            :description="$waste->items->count().' '.($waste->items->count() === 1 ? 'línea' : 'líneas')">
            <x-ui.infolist.repeatable>
                @foreach($waste->items as $item)
                    <x-ui.infolist.repeatable-item :cols="6">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                            @if($item->notes)
                                <span class="block text-xs font-normal text-gray-500">{{ $item->notes }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Motivo">
                            <x-ui.badge variant="slate" size="sm" :dot="false">{{ $item->reason_label }}</x-ui.badge>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Cantidad" :value="$fmtQty($item->quantity)" strong />
                        <x-ui.infolist.entry label="Costo unit." :value="$money($item->unit_cost)" />
                        <x-ui.infolist.entry label="Valor" class="sm:text-right">
                            <span class="font-semibold text-state-error">{{ $money($item->total_value) }}</span>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>

    @if($canVoid)
        <x-ui.confirm-modal name="void-waste" title="¿Anular merma?" :route="route('inventory.wastes.void', $waste)"
            method="PATCH" variant="error" confirmLabel="Anular merma" :show="$errors->has('void_reason')"
            description="Las existencias regresan al almacén. La merma queda anulada; no se borra.">
            <x-ui.forms.textarea label="Motivo de la anulación" name="void_reason" :rows="2" required minlength="5" maxlength="255"
                placeholder="Ej: se registró en el almacén equivocado"
                :error="$errors->first('void_reason')">{{ old('void_reason') }}</x-ui.forms.textarea>
        </x-ui.confirm-modal>
    @endif
</x-app-layout>
