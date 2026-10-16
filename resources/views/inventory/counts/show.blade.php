{{-- Toma física — show / revisión antes de aplicar (v1.5.0 REQ-2.1, /filament-show).
     Recibe $count (warehouse, category, creator, applier, canceler, items.product) y
     $showComparison: en un conteo ciego en borrador, la comparación contra el sistema
     solo la ve quien puede aplicar. --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '-' : '').$currency.number_format(abs((float) $v), 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $signed = fn ($q) => ($q > 0 ? '+' : '').$fmtQty($q);

    $counted = $count->items->filter->isCounted();
    $pending = $count->items->count() - $counted->count();
    $shortages = $counted->filter(fn ($i) => $i->current_difference < 0);
    $surpluses = $counted->filter(fn ($i) => $i->current_difference > 0);
    $netValue = $count->isApplied() ? (float) $count->difference_value : $counted->sum(fn ($i) => $i->difference_value);

    $canCount = $count->isDraft() && auth()->user()->can('inventory_counts.count');
    $canCancel = $count->isDraft() && auth()->user()->can('inventory_counts.create');
    $canApply = $count->isDraft() && auth()->user()->can('inventory_counts.apply');
@endphp

<x-app-layout :title="'Toma física '.$count->number">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="'Toma física '.$count->number" :description="$count->warehouse->name.' · '.$count->scope_label">
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.counts.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @if($canCount)
                    <x-ui.button href="{{ route('inventory.counts.count', $count) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-pencil-square">
                        Contar
                    </x-ui.button>
                @endif
                @if($canApply)
                    <x-ui.button variant="primary" iconLeft="heroicon-s-check" x-data x-on:click="$dispatch('open-modal', 'apply-count')">
                        Aplicar
                    </x-ui.button>
                @endif
            </x-slot:actions>

            <x-slot:secondary>
                <x-ui.button href="{{ route('inventory.counts.pdf', $count) }}" target="_blank" variant="secondary" appearance="ghost"
                    class="w-full justify-start" iconLeft="heroicon-s-printer">
                    PDF
                </x-ui.button>
                @if($canCancel)
                    <x-ui.button variant="error" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-x-circle"
                        x-data x-on:click="$dispatch('open-modal', 'cancel-count')">
                        Cancelar toma
                    </x-ui.button>
                @endif
            </x-slot:secondary>
        </x-ui.page-header>

        @if($count->status === \App\Models\Inventory\InventoryCount::STATUS_CANCELED)
            <x-ui.alert variant="error">
                Cancelada por <strong>{{ $count->canceler->name ?? '—' }}</strong> el {{ $count->canceled_at?->format('d/m/Y h:i A') }}. No ajustó ninguna existencia.
            </x-ui.alert>
        @elseif($count->isDraft() && ! $showComparison)
            <x-ui.alert variant="neutral" icon="heroicon-s-eye-slash" title="Conteo ciego:">
                las diferencias las ve quien revisa y aplica la toma.
            </x-ui.alert>
        @elseif($count->isDraft())
            <x-ui.alert variant="neutral" title="Borrador:">
                se compara con la existencia del {{ $count->created_at->format('d/m/Y h:i A') }}; el stock cambia al aplicar.
            </x-ui.alert>
        @endif

        {{-- Datos de la toma --}}
        <x-ui.infolist.section title="Datos de la toma" icon="heroicon-o-clipboard-document-check" :cols="3">
            <x-ui.infolist.entry label="Número" :value="$count->number" strong />
            <x-ui.infolist.entry label="Estado">
                <x-ui.badge :variant="$count->status_variant" size="sm" :dot="false">{{ $count->status_label }}</x-ui.badge>
            </x-ui.infolist.entry>
            <x-ui.infolist.entry label="Almacén" :value="$count->warehouse->name" strong />
            <x-ui.infolist.entry label="Alcance" :value="$count->scope_label" />
            <x-ui.infolist.entry label="Conteo ciego" :value="$count->blind ? 'Sí' : 'No'" />
            <x-ui.infolist.entry label="Creada" :value="($count->creator->name ?? '—').' · '.$count->created_at->format('d/m/Y h:i A')" />
            @if($count->isApplied())
                <x-ui.infolist.entry label="Aplicada" :value="($count->applier->name ?? '—').' · '.$count->applied_at?->format('d/m/Y h:i A')" />
            @endif
            <x-ui.infolist.entry label="Notas" :value="$count->notes" full />
        </x-ui.infolist.section>

        @if($showComparison)
            {{-- Resumen de diferencias --}}
            <x-ui.infolist.section title="Resumen" icon="heroicon-o-scale" :cols="4"
                :description="'Valor al costo de la mercancía que falta o apareció; no es dinero de caja.'.($count->isApplied() ? ' Costo del día en que se aplicó.' : ' Se usa el costo actual hasta aplicar.')">
                <x-ui.infolist.entry label="Contados" :value="$counted->count().' de '.$count->items->count()" strong />
                <x-ui.infolist.entry label="Faltantes">
                    <span class="font-semibold text-state-error">{{ $shortages->count() }} {{ $shortages->count() === 1 ? 'producto' : 'productos' }}</span>
                    @if($shortages->isNotEmpty())
                        <span class="block text-xs text-gray-500">{{ $money($shortages->sum(fn ($i) => $i->difference_value)) }}</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Sobrantes">
                    <span class="font-semibold text-emerald-600">{{ $surpluses->count() }} {{ $surpluses->count() === 1 ? 'producto' : 'productos' }}</span>
                    @if($surpluses->isNotEmpty())
                        <span class="block text-xs text-gray-500">+{{ $money($surpluses->sum(fn ($i) => $i->difference_value)) }}</span>
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Valor neto ajustado">
                    <span @class(['text-xl font-bold', 'text-state-error' => $netValue < 0, 'text-emerald-600' => $netValue > 0, 'text-gray-900' => $netValue == 0])>
                        {{ $netValue > 0 ? '+' : '' }}{{ $money($netValue) }}
                    </span>
                    <span class="block text-xs text-gray-500">{{ $netValue < 0 ? 'Pérdida de inventario' : ($netValue > 0 ? 'Aumento de inventario' : 'El inventario cuadra') }}</span>
                </x-ui.infolist.entry>
            </x-ui.infolist.section>
        @endif

        {{-- Líneas contadas --}}
        <x-ui.infolist.section title="Productos contados" icon="heroicon-o-queue-list" :cols="0"
            :description="$pending > 0 ? $pending.' '.($pending === 1 ? 'producto sin contar' : 'productos sin contar').': no generan ajuste.' : 'Todos los productos del alcance fueron contados.'">
            <x-ui.infolist.repeatable :empty="$counted->isEmpty()" emptyIcon="heroicon-o-clipboard-document-list"
                emptyTitle="Todavía no se ha contado nada" emptyDescription="Usa Contar para registrar las cantidades.">
                @foreach($counted as $item)
                    @php
                        $diff = $item->current_difference;
                        $diffVariant = $diff < 0 ? 'error' : ($diff > 0 ? 'success' : 'slate');
                    @endphp
                    <x-ui.infolist.repeatable-item :cols="$showComparison ? 6 : 3">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong>
                            {{ $item->product->name ?? 'Producto eliminado' }}
                            @if($item->product?->sku || $item->product?->barcode)
                                <span class="block text-xs font-normal text-gray-400 font-mono">{{ collect([$item->product->sku, $item->product->barcode])->filter()->join(' · ') }}</span>
                            @endif
                        </x-ui.infolist.entry>
                        @if($showComparison)
                            <x-ui.infolist.entry label="Al iniciar" :value="$fmtQty($item->system_quantity)" />
                        @endif
                        <x-ui.infolist.entry label="Contado" :value="$fmtQty($item->counted_quantity)" strong />
                        @if($showComparison)
                            <x-ui.infolist.entry label="Diferencia">
                                <x-ui.badge :variant="$diffVariant" size="sm" :dot="false">
                                    {{ $diff == 0 ? 'Cuadra' : ($diff < 0 ? 'Faltan ' : 'Sobran ').$fmtQty(abs($diff)) }}
                                </x-ui.badge>
                            </x-ui.infolist.entry>
                            <x-ui.infolist.entry label="Valor" class="sm:text-right">
                                <span @class(['font-semibold', 'text-state-error' => $item->difference_value < 0, 'text-emerald-600' => $item->difference_value > 0, 'text-gray-500' => $item->difference_value == 0])>
                                    {{ $item->difference_value > 0 ? '+' : '' }}{{ $money($item->difference_value) }}
                                </span>
                            </x-ui.infolist.entry>
                        @endif
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>
        </x-ui.infolist.section>
    </div>

    @if($canApply)
        <x-ui.confirm-modal name="apply-count" title="¿Aplicar toma física?" :route="route('inventory.counts.apply', $count)"
            confirmLabel="Aplicar toma"
            description="Cada producto con diferencia ajusta el stock del almacén y la toma queda cerrada. Los no contados no cambian.">
            <x-ui.alert variant="neutral" :icon="false">
                <dl class="space-y-1">
                    <div class="flex justify-between gap-4">
                        <dt>Faltantes · {{ $shortages->count() }}</dt>
                        <dd class="font-semibold text-state-error tabular-nums">{{ $money($shortages->sum(fn ($i) => $i->difference_value)) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt>Sobrantes · {{ $surpluses->count() }}</dt>
                        <dd class="font-semibold text-emerald-600 tabular-nums">+{{ $money($surpluses->sum(fn ($i) => $i->difference_value)) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-gray-200 pt-1">
                        <dt class="font-semibold text-gray-800">Valor neto</dt>
                        <dd class="font-semibold tabular-nums text-gray-900">{{ $netValue > 0 ? '+' : '' }}{{ $money($netValue) }}</dd>
                    </div>
                </dl>
                <p class="mt-2 text-xs text-gray-500">No mueve dinero de caja ni banco.</p>
            </x-ui.alert>
        </x-ui.confirm-modal>
    @endif

    @if($canCancel)
        <x-ui.confirm-modal name="cancel-count" title="¿Cancelar toma física?" :route="route('inventory.counts.cancel', $count)"
            method="PATCH" variant="error" confirmLabel="Cancelar toma"
            description="La toma queda cancelada y no ajusta ninguna existencia. Lo contado se conserva como referencia." />
    @endif
</x-app-layout>
