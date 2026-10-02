{{-- Detalle del almacén — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Fila 1: datos | resumen de inventario (50/50). Fila 2: productos del almacén.
     Recibe $warehouse, $stocks (los LIST_LIMIT con más stock, con product.unit),
     $summary (agregados de todo el almacén), $listLimit y $types (modal de editar). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $items = (int) $summary->items;
@endphp

<x-app-layout :title="$warehouse->name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$warehouse->name" description="Ver almacén">
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.warehouses.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                <x-ui.button variant="primary" iconLeft="heroicon-s-pencil-square"
                    x-data @click="$dispatch('open-modal', 'edit-warehouse-{{ $warehouse->id }}')">
                    Editar
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @unless($warehouse->is_active)
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-3 flex items-center gap-2 text-sm text-gray-600">
                <x-heroicon-s-no-symbol class="w-5 h-5 shrink-0" />
                Almacén inactivo — no se usa para nuevas ventas ni movimientos.
            </div>
        @endunless

        {{-- Fila 1: datos | resumen --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            <x-ui.infolist.section title="Datos del almacén" icon="heroicon-o-building-storefront" :cols="2">
                <x-ui.infolist.entry label="Nombre" :value="$warehouse->name" strong />
                <x-ui.infolist.entry label="Estado">
                    <x-ui.badge :variant="$warehouse->is_active ? 'success' : 'slate'" size="sm" :dot="false">
                        {{ $warehouse->is_active ? 'Operativo' : 'Inactivo' }}
                    </x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Tipo">
                    <x-ui.badge variant="info" size="sm" :dot="false"
                        :icon="$warehouse->type === \App\Models\Inventory\Warehouse::TYPE_MOBILE ? 'heroicon-s-truck' : 'heroicon-s-building-office-2'">
                        {{ $warehouse->type_label }}
                    </x-ui.badge>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Creado" :value="$warehouse->created_at->format('d/m/Y')" />
                <x-ui.infolist.entry label="Dirección" :value="$warehouse->address" full />
                <x-ui.infolist.entry label="Descripción" :value="$warehouse->description" full />
                @if(module_enabled('accounting.advanced'))
                    <x-ui.infolist.entry label="Cuenta contable" full
                        :value="$warehouse->accountingAccount ? $warehouse->accountingAccount->code.' — '.$warehouse->accountingAccount->name : null" />
                @endif
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Resumen de inventario" icon="heroicon-o-chart-bar" :cols="2">
                <x-ui.infolist.entry label="Productos con stock">
                    <span class="text-xl font-bold text-gray-900">{{ (int) $summary->with_stock }}</span>
                    <span class="text-gray-500">de {{ $items }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Valor a costo">
                    <span class="text-xl font-bold text-zertix-primary-700">{{ $money($summary->value) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Unidades en existencia" :value="$fmtQty($summary->units)" />
                <x-ui.infolist.entry label="Alertas">
                    <span class="inline-flex flex-wrap gap-1.5">
                        @if($summary->out_of_stock > 0)
                            <x-ui.badge variant="error" size="sm" :dot="false">{{ (int) $summary->out_of_stock }} agotado(s)</x-ui.badge>
                        @endif
                        @if($summary->low_stock > 0)
                            <x-ui.badge variant="warning" size="sm" :dot="false">{{ (int) $summary->low_stock }} bajo mínimo</x-ui.badge>
                        @endif
                        @if(! $summary->out_of_stock && ! $summary->low_stock && $items)
                            <x-ui.badge variant="success" size="sm" :dot="false">Sin alertas</x-ui.badge>
                        @endif
                    </span>
                </x-ui.infolist.entry>
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: productos del almacén --}}
        <x-ui.infolist.section title="Productos en este almacén" icon="heroicon-o-cube" :cols="0"
            :description="$items > $listLimit ? 'Los '.$listLimit.' con más existencia de '.$items : $items.' '.($items === 1 ? 'producto' : 'productos')">
            <x-ui.infolist.repeatable :empty="$stocks->isEmpty()" emptyIcon="heroicon-o-cube"
                emptyTitle="Sin productos" emptyDescription="Este almacén todavía no tiene existencias registradas.">
                @foreach($stocks as $stock)
                    @php
                        $product = $stock->product;
                        $unit = $product?->unit?->abbreviation;
                        $c = $product?->stockCondition((float) $stock->quantity, (float) $stock->min_stock);
                    @endphp
                    <x-ui.infolist.repeatable-item :cols="6">
                        <x-ui.infolist.entry label="Producto" class="col-span-2" strong
                            :href="$product && ! $product->trashed() && auth()->user()->can('products.view') ? route('inventory.products.show', $product) : null">
                            {{ $product->name ?? 'Producto eliminado' }}
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Stock">
                            <span class="font-semibold text-gray-900">{{ $fmtQty($stock->quantity) }}</span>
                            @if($unit)<span class="text-gray-500">{{ $unit }}</span>@endif
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Mínimo" :value="$stock->min_stock > 0 ? $fmtQty($stock->min_stock) : null" />
                        <x-ui.infolist.entry label="Valor a costo" :value="$product && $stock->quantity > 0 ? $money($stock->quantity * $product->cost) : null" />
                        <x-ui.infolist.entry label="Condición" class="sm:text-right">
                            @if($c)
                                <x-ui.badge :variant="$c['variant']" size="sm" :dot="false">{{ $c['label'] }}</x-ui.badge>
                            @endif
                        </x-ui.infolist.entry>
                    </x-ui.infolist.repeatable-item>
                @endforeach
            </x-ui.infolist.repeatable>

            @if($items > $listLimit)
                <p class="mt-4 text-xs text-slate-500">
                    <a href="{{ route('inventory.stocks.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todo el stock</a>
                </p>
            @endif
        </x-ui.infolist.section>
    </div>

    {{-- Modal de editar (mismo partial del listado) --}}
    @include('inventory.warehouses.partials.modals', ['warehouses' => collect([$warehouse]), 'types' => $types])
</x-app-layout>
