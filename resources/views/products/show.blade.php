{{-- Ficha del producto/servicio — patrón Infolist con pestañas (/filament-show,
     docs/ui/infolist.md). Recibe $product (category, unit, productTaxes,
     stocks.warehouse cargados), $totalStock, $totalMin, $totalMax (null = sin tope), $condition
     (Product::stockCondition()), $profit/$margin/$markup, $tabLimit y
     $movements/$movementsCount ($movements es null si no aplica la pestaña). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $pct = fn ($v) => $v === null ? null : number_format($v, 1).'%';
    $isService = $product->isService();
    $unit = $product->unit?->abbreviation;
    $taxKeys = $product->taxes();

    $tabs = [['name' => 'sheet', 'label' => 'Ficha', 'icon' => 'heroicon-o-cube']];
    if ($movements !== null) {
        $tabs[] = ['name' => 'movements', 'label' => 'Movimientos', 'icon' => 'heroicon-o-arrows-right-left', 'count' => $movementsCount];
    }
@endphp

<x-app-layout :title="$product->name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$product->name" :description="$isService ? 'Ver servicio' : 'Ver producto'">
            <x-slot:actions>
                <x-ui.button href="{{ route('inventory.products.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @can('products.delete')
                    <x-ui.button variant="error" appearance="outline" iconLeft="heroicon-s-trash"
                        x-data @click="$dispatch('open-modal', 'confirm-deletion-product-{{ $product->id }}')">
                        Eliminar
                    </x-ui.button>
                @endcan
                @can('products.edit')
                    <x-ui.button href="{{ route('inventory.products.edit', $product) }}" variant="primary" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.infolist.tabs :tabs="$tabs">

            {{-- Ficha --}}
            <x-ui.infolist.tab name="sheet">

                {{-- Fila 1: identificación (70%) | estado de stock (30%) --}}
                <div class="grid grid-cols-1 lg:grid-cols-10 gap-5 items-start">

                    <x-ui.infolist.section title="Identificación" icon="heroicon-o-identification" :cols="0" flat class="lg:col-span-7">
                        <div class="flex flex-col sm:flex-row gap-6">
                            <div class="shrink-0">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                         class="w-full sm:w-44 h-44 rounded-xl object-cover border border-gray-100 shadow-sm bg-white">
                                @else
                                    <div class="w-full sm:w-44 h-44 rounded-xl border border-dashed border-gray-200 bg-slate-50 flex flex-col items-center justify-center text-slate-400">
                                        <x-heroicon-o-photo class="w-10 h-10" />
                                        <span class="mt-1 text-xs">Sin imagen</span>
                                    </div>
                                @endif
                            </div>

                            <dl class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-7 content-start">
                                <x-ui.infolist.entry label="Nombre" :value="$product->name" strong class="sm:col-span-2" />
                                <x-ui.infolist.entry label="SKU">
                                    @if($product->sku)
                                        <span class="font-mono">{{ $product->sku }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Código de barras">
                                    @if($product->barcode)
                                        <span class="font-mono">{{ $product->barcode }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Categoría" :value="$product->category?->name" />
                                <x-ui.infolist.entry label="Unidad de medida"
                                    :value="$product->unit ? $product->unit->name.($unit ? ' ('.$unit.')' : '') : null" />
                                <x-ui.infolist.entry label="Registrado" :value="$product->created_at->format('d/m/Y')" />
                                <x-ui.infolist.entry label="Descripción" :value="$product->description" class="sm:col-span-2" />
                            </dl>
                        </div>
                    </x-ui.infolist.section>

                    <x-ui.infolist.section title="Estado de stock" icon="heroicon-o-archive-box" :cols="1" flat class="lg:col-span-3">
                        <x-ui.infolist.entry label="En stock (todos los almacenes)">
                            @unless($isService)
                                <span @class([
                                    'text-2xl font-bold',
                                    'text-state-error' => $condition['key'] === 'out',
                                    'text-amber-600' => $condition['key'] === 'low',
                                    'text-gray-900' => $condition['key'] === 'available',
                                ])>{{ $fmtQty($totalStock) }}</span>
                                @if($unit)
                                    <span class="text-sm text-gray-500">{{ $unit }}</span>
                                @endif
                            @endunless
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Condición">
                            <x-ui.badge :variant="$condition['variant']" size="sm" :dot="false">{{ $condition['label'] }}</x-ui.badge>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Stock mínimo" :value="! $isService && $totalMin > 0 ? $fmtQty($totalMin).($unit ? ' '.$unit : '') : null" />
                        <x-ui.infolist.entry label="Stock máximo" :value="! $isService && $totalMax !== null ? $fmtQty($totalMax).($unit ? ' '.$unit : '') : null" />
                        <x-ui.infolist.entry label="Tipo">
                            <x-ui.badge :variant="$isService ? 'primary' : 'info'" size="sm" :dot="false"
                                :icon="$isService ? 'heroicon-s-wrench-screwdriver' : 'heroicon-s-cube'">
                                {{ $isService ? 'Servicio' : 'Producto' }}
                            </x-ui.badge>
                        </x-ui.infolist.entry>
                        <x-ui.infolist.entry label="Estado">
                            <x-ui.badge :variant="$product->is_active ? 'success' : 'slate'" size="sm" :dot="false">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </x-ui.badge>
                        </x-ui.infolist.entry>
                    </x-ui.infolist.section>
                </div>

                {{-- Fila 2: precios --}}
                <x-ui.infolist.section title="Precios" icon="heroicon-o-currency-dollar" flat>
                    <x-ui.infolist.entry label="Precio de costo" :value="$money($product->cost)" />
                    <x-ui.infolist.entry label="Precio de venta">
                        <span class="text-lg font-bold text-zertix-primary-700">{{ $money($product->price) }}</span>
                        <span class="block text-xs text-gray-400">sin impuestos</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Precio con impuestos" :value="$money($product->price_with_tax)" />
                    <x-ui.infolist.entry label="Impuestos">
                        <span class="inline-flex flex-wrap gap-1">
                            @forelse($taxKeys as $key)
                                <x-ui.badge variant="info" size="sm" :dot="false">{{ config("impuestos.$key.label") }}</x-ui.badge>
                            @empty
                                <x-ui.badge variant="slate" size="sm" :dot="false">Sin impuesto</x-ui.badge>
                            @endforelse
                        </span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Ganancia por unidad">
                        <span @class(['font-semibold', 'text-emerald-600' => $profit > 0, 'text-state-error' => $profit < 0])>{{ $money($profit) }}</span>
                    </x-ui.infolist.entry>
                    <x-ui.infolist.entry label="Margen" :value="$pct($margin)" />
                    <x-ui.infolist.entry label="Markup" :value="$pct($markup)" />
                </x-ui.infolist.section>

                {{-- Fila 3: stock por almacén (solo productos) --}}
                @unless($isService)
                    <x-ui.infolist.section title="Stock por almacén" icon="heroicon-o-building-storefront" :cols="0" flat
                        :description="$product->stocks->count().' '.($product->stocks->count() === 1 ? 'almacén' : 'almacenes')">
                        <x-ui.infolist.repeatable :empty="$product->stocks->isEmpty()" emptyIcon="heroicon-o-building-storefront"
                            emptyTitle="Sin stock registrado" emptyDescription="Este producto todavía no tiene existencias en ningún almacén.">
                            @foreach($product->stocks as $stock)
                                @php $c = $product->stockCondition((float) $stock->quantity, (float) $stock->min_stock, $stock->max_stock !== null ? (float) $stock->max_stock : null); @endphp
                                <x-ui.infolist.repeatable-item :cols="6">
                                    <x-ui.infolist.entry label="Almacén" strong>
                                        {{ $stock->warehouse->name ?? 'Almacén eliminado' }}
                                        @if($stock->warehouse && ! $stock->warehouse->is_active)
                                            <span class="block text-xs font-normal text-gray-400">Inactivo</span>
                                        @endif
                                    </x-ui.infolist.entry>
                                    <x-ui.infolist.entry label="Tipo de almacén">
                                        @if($stock->warehouse)
                                            <x-ui.badge variant="slate" size="sm" :dot="false">{{ $stock->warehouse->type_label }}</x-ui.badge>
                                        @endif
                                    </x-ui.infolist.entry>
                                    <x-ui.infolist.entry label="Stock">
                                        <span class="font-semibold text-gray-900">{{ $fmtQty($stock->quantity) }}</span>
                                        @if($unit)<span class="text-gray-500">{{ $unit }}</span>@endif
                                    </x-ui.infolist.entry>
                                    <x-ui.infolist.entry label="Mínimo" :value="$stock->min_stock > 0 ? $fmtQty($stock->min_stock) : null" />
                                    <x-ui.infolist.entry label="Máximo" :value="$stock->max_stock !== null ? $fmtQty($stock->max_stock) : 'Sin tope'" />
                                    <x-ui.infolist.entry label="Condición" class="sm:text-right">
                                        <x-ui.badge :variant="$c['variant']" size="sm" :dot="false">{{ $c['label'] }}</x-ui.badge>
                                    </x-ui.infolist.entry>
                                </x-ui.infolist.repeatable-item>
                            @endforeach
                        </x-ui.infolist.repeatable>
                    </x-ui.infolist.section>
                @endunless
            </x-ui.infolist.tab>

            {{-- Movimientos (historial de stock) --}}
            @if($movements !== null)
                <x-ui.infolist.tab name="movements">
                    <x-ui.infolist.repeatable :empty="$movements->isEmpty()" emptyIcon="heroicon-o-arrows-right-left"
                        emptyTitle="Sin movimientos" emptyDescription="Este producto todavía no tiene movimientos de inventario.">
                        @foreach($movements as $movement)
                            @php $qty = (float) $movement->quantity; @endphp
                            <x-ui.infolist.repeatable-item :cols="7">
                                <x-ui.infolist.entry label="Fecha">
                                    {{ $movement->created_at->format('d/m/Y') }}
                                    <span class="block text-xs text-gray-400">{{ $movement->created_at->format('h:i A') }}</span>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Tipo">
                                    <x-ui.badge :hex="$movement->type_hex" size="sm" :dot="false">{{ $movement->type_label }}</x-ui.badge>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Almacén">
                                    {{ $movement->warehouse->name ?? 'Almacén eliminado' }}
                                    @if($movement->toWarehouse)
                                        <span class="block text-xs text-gray-400">→ {{ $movement->toWarehouse->name }}</span>
                                    @endif
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Cantidad">
                                    <span @class(['font-semibold', 'text-emerald-600' => $qty > 0, 'text-state-error' => $qty < 0])>
                                        {{ $qty > 0 ? '+' : '' }}{{ $fmtQty($qty) }}
                                    </span>
                                </x-ui.infolist.entry>
                                <x-ui.infolist.entry label="Antes" :value="$fmtQty($movement->previous_stock)" />
                                <x-ui.infolist.entry label="Después" :value="$fmtQty($movement->current_stock)" strong />
                                <x-ui.infolist.entry label="Usuario" :value="$movement->user?->name" />
                                <x-ui.infolist.entry label="Notas" :value="$movement->description" class="col-span-2 sm:col-span-full" />
                            </x-ui.infolist.repeatable-item>
                        @endforeach
                    </x-ui.infolist.repeatable>

                    @if($movementsCount > $tabLimit)
                        <p class="text-xs text-slate-500">
                            Mostrando los {{ $tabLimit }} más recientes de {{ $movementsCount }}.
                            <a href="{{ route('inventory.movements.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todos</a>
                        </p>
                    @endif
                </x-ui.infolist.tab>
            @endif
        </x-ui.infolist.tabs>
    </div>

    @can('products.delete')
        <x-ui.confirm-deletion-modal
            :id="'product-'.$product->id"
            title="¿Eliminar producto?"
            :itemName="$product->name"
            type="el producto"
            :route="route('inventory.products.destroy', $product)">
            <strong>Aviso:</strong> Esta operación se puede deshacer desde la papelera.
        </x-ui.confirm-deletion-modal>
    @endcan
</x-app-layout>
