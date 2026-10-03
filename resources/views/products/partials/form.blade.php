{{-- Formulario de producto/servicio en pestañas (x-ui.infolist.tabs, mismo componente de los
     show): Identificación (el tipo Producto/Servicio va primero), Precio e impuestos e
     Inventario (solo productos). Lo usan products/create.blade.php y edit.blade.php sin cambios.
     Con errores de validación abre en la primera pestaña que tenga uno, marcada con punto rojo.

     Recibe (ProductCatalogService::getForForm()):
       $categories, $units           — catálogos de los selects.
       $itbisTaxes, $addonTaxes      — impuestos scope 'product' (ITBIS excluyente / apilables).
       $warehouses                   — almacenes activos (sección Inventario al crear).
     Opcional:
       $product                      — solo en edit, con `stocks.warehouse` cargado. --}}
@php
    $isEdit = isset($product);
    $tracking = module_enabled('inventory.tracking');
    $symbol = config('regional.currency_symbol');

    // config('impuestos.default') puede ser null (en RD no todo lleva ITBIS): sin default, nada premarcado.
    $oldTaxKeys = old('tax_keys', $isEdit
        ? $product->taxes()
        : (config('impuestos.default') ? [config('impuestos.default')] : []));

    $taxRates = $itbisTaxes->merge($addonTaxes)->map(fn ($tax) => (float) ($tax['rate'] ?? 0));

    // Qué pestaña tiene errores: con validación fallida se abre la primera de ellas.
    $tabErrors = [
        'identificacion' => $errors->hasAny(['type', 'name', 'sku', 'barcode', 'category_id', 'unit_id', 'description', 'image', 'is_active']),
        'precio' => $errors->hasAny(['cost', 'price', 'tax_keys', 'tax_keys.*']),
        'inventario' => collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'inventory.') || str_starts_with($k, 'stocks.')),
    ];
    $tabs = [
        ['name' => 'identificacion', 'label' => 'Identificación', 'icon' => 'heroicon-o-identification', 'error' => $tabErrors['identificacion']],
        ['name' => 'precio', 'label' => 'Precio e impuestos', 'icon' => 'heroicon-o-banknotes', 'error' => $tabErrors['precio']],
        ['name' => 'inventario', 'label' => 'Inventario', 'icon' => 'heroicon-o-archive-box', 'show' => '!isService', 'error' => $tabErrors['inventario']],
    ];
    $defaultTab = array_key_first(array_filter($tabErrors)) ?? 'identificacion';
@endphp

<div
    x-data="{
        isService: @js(old('type', $product->type ?? 'product') === 'service'),
        categoryId: @js((string) old('category_id', $product->category_id ?? '')),
        unitId: @js((string) old('unit_id', $product->unit_id ?? '')),
        servicesCategoryId: @js($categories->firstWhere('name', 'Servicios')?->id),
        unidadUnitId: @js($units->firstWhere('name', 'Unidad')?->id),
        taxRates: @js($taxRates),
        rate: 0,
        selectType(service) {
            this.isService = service;
            if (service) {
                if (this.servicesCategoryId) this.categoryId = String(this.servicesCategoryId);
                if (this.unidadUnitId) this.unitId = String(this.unidadUnitId);
            }
        },
        // Suma de las tasas marcadas en la sección de impuestos (la usa la calculadora de ITBIS).
        syncRate() {
            this.rate = [...this.$root.querySelectorAll('input[name=\'tax_keys[]\']:checked')]
                .reduce((sum, el) => sum + (this.taxRates[el.value] || 0), 0);
        },
    }"
    x-init="syncRate()">

<x-ui.infolist.tabs :tabs="$tabs" :default="$defaultTab">

    {{-- PESTAÑA 1: Identificación — primero el tipo, luego nombre y demás --}}
    <x-ui.infolist.tab name="identificacion" class="!space-y-6">
        <div>
        <span class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de ítem <span class="text-state-error">*</span></span>
        <input type="hidden" name="type" :value="isService ? 'service' : 'product'">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ([
                ['service' => false, 'icon' => 'heroicon-o-cube', 'title' => 'Producto', 'text' => 'Mercancía física: lleva existencias y se descuenta del almacén al venderse.'],
                ['service' => true, 'icon' => 'heroicon-o-wrench-screwdriver', 'title' => 'Servicio', 'text' => 'Instalación, flete, mano de obra… No lleva existencias ni almacén.'],
            ] as $option)
                <button type="button" @click="selectType({{ $option['service'] ? 'true' : 'false' }})"
                    :class="isService === {{ $option['service'] ? 'true' : 'false' }}
                        ? 'border-zertix-primary ring-1 ring-zertix-primary/30 bg-zertix-primary/5'
                        : 'border-gray-200 hover:border-gray-300 bg-white'"
                    class="flex items-start gap-3 rounded-xl border p-4 text-left transition-colors">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        :class="isService === {{ $option['service'] ? 'true' : 'false' }} ? 'bg-zertix-primary/10 text-zertix-primary-dark' : 'bg-gray-100 text-gray-500'">
                        <x-dynamic-component :component="$option['icon']" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-900">{{ $option['title'] }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500">{{ $option['text'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>
        @error('type')
            <p class="mt-1.5 text-xs text-state-error break-words">{{ $message }}</p>
        @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr] gap-x-8 gap-y-6 border-t border-gray-100 pt-6">
            {{-- Imagen --}}
            <div>
                <span class="block text-xs font-semibold text-slate-600 mb-1.5">Imagen</span>
                <div class="flex items-start gap-3">
                    @if($isEdit && $product->image_path)
                        <div class="text-center">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                 class="w-28 h-28 rounded-xl border border-gray-100 object-cover bg-white">
                            <span class="mt-1 block text-xs text-gray-400">Actual</span>
                        </div>
                    @endif
                    <x-ui.forms.file-input name="image" accept="image/*" :dropzone="true" :preview="true" size="md" dropzoneLabel="Subir imagen"
                        :hint="$isEdit && $product->image_path ? 'Sube otra para reemplazarla' : 'JPG, PNG o WEBP, hasta 2 MB'"
                        :error="$errors->first('image')" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5 content-start">
                <div class="sm:col-span-2">
                    <x-ui.forms.input label="Nombre" name="name" required
                        value="{{ old('name', $product->name ?? '') }}"
                        placeholder="Ej: Funda de Hielo 10lb"
                        :error="$errors->first('name')" />
                </div>

                {{-- Códigos (REQ-1.2): opcionales, únicos si tienen valor --}}
                <x-ui.forms.input label="SKU / Código interno" name="sku"
                    value="{{ old('sku', $product->sku ?? '') }}"
                    placeholder="Ej: HIE-10LB" iconLeft="heroicon-o-hashtag"
                    hint="Opcional. El código que usa tu negocio."
                    :error="$errors->first('sku')" />
                <x-ui.forms.input label="Código de barras" name="barcode" autocomplete="off"
                    value="{{ old('barcode', $product->barcode ?? '') }}"
                    placeholder="Escanea o escribe el código" iconLeft="heroicon-o-qr-code"
                    hint="Opcional. Con él, el TPV lo agrega con solo escanearlo."
                    :error="$errors->first('barcode')" />

                <x-ui.forms.select label="Categoría" name="category_id" x-model="categoryId" required
                    placeholder="Seleccione una categoría..."
                    :error="$errors->first('category_id')">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-ui.forms.select>

                {{-- Un servicio usa siempre la unidad "Unidad": el select se cambia por un campo fijo. --}}
                <div>
                    <template x-if="!isService">
                        <x-ui.forms.select label="Unidad de medida" name="unit_id" x-model="unitId" required
                            placeholder="Seleccione unidad..."
                            :error="$errors->first('unit_id')">
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                            @endforeach
                        </x-ui.forms.select>
                    </template>
                    <template x-if="isService">
                        <div>
                            <input type="hidden" name="unit_id" :value="unitId">
                            <x-ui.forms.input label="Unidad de medida" name="unit_display" disabled
                                value="{{ $units->firstWhere('name', 'Unidad')?->name ?? 'Unidad' }}"
                                hint="Fija para servicios." />
                        </div>
                    </template>
                </div>

                <div class="sm:col-span-2">
                    <x-ui.forms.textarea label="Descripción" name="description" :rows="3"
                        placeholder="Detalles adicionales..."
                        :error="$errors->first('description')">{{ old('description', $product->description ?? '') }}</x-ui.forms.textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-ui.forms.toggle label="Activo" name="is_active" value="1"
                        description="Solo los ítems activos aparecen en el TPV y en las cotizaciones."
                        :checked="(bool) old('is_active', $product->is_active ?? true)" />
                </div>
            </div>
        </div>
    </x-ui.infolist.tab>

    {{-- PESTAÑA 2: Precio e impuestos --}}
    <x-ui.infolist.tab name="precio" class="!space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <div>
                <x-ui.forms.input label="Costo ({{ $symbol }})" name="cost" type="number" step="0.0001" min="0" required
                    value="{{ old('cost', isset($product) ? (float) $product->cost : '') }}" placeholder="0.00"
                    :error="$errors->first('cost')" />
                <p class="mt-1.5 text-xs text-slate-400 break-words" x-show="!isService">Lo que pagas al proveedor por unidad. Las compras lo recalculan con el costo promedio.</p>
                <p class="mt-1.5 text-xs text-slate-400 break-words" x-show="isService" x-cloak>Deja 0 si es mano de obra propia; si lo subcontratas, escribe ese costo directo.</p>
            </div>
            <x-ui.forms.input label="Precio de venta ({{ $symbol }})" name="price" type="number" step="0.01" min="0" required
                value="{{ old('price', $product->price ?? '') }}" placeholder="0.00"
                hint="Precio sin impuesto. Los impuestos marcados abajo se suman al cobrar."
                :error="$errors->first('price')" />
        </div>

        {{-- Impuestos: ITBIS excluyente (radios, regla DGII) + apilables (checkboxes), todos
             con name="tax_keys[]" para llegar en un solo array. --}}
        <div class="mt-6 border-t border-gray-100 pt-5" @change="syncRate()">
            <p class="text-xs font-semibold text-slate-600 mb-2">ITBIS <span class="font-normal text-slate-400">(elige uno)</span></p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($itbisTaxes as $key => $tax)
                    <x-ui.forms.radio label="{{ $tax['label'] }}" name="tax_keys[]" id="tax_key_{{ $key }}"
                        value="{{ $key }}" :checked="in_array($key, $oldTaxKeys)" />
                @endforeach
                <x-ui.forms.radio label="Sin ITBIS" name="tax_keys[]" id="tax_key_none" value=""
                    :checked="collect($oldTaxKeys)->intersect($itbisTaxes->keys())->isEmpty()" />
            </div>

            @if($addonTaxes->isNotEmpty())
                <p class="text-xs font-semibold text-slate-600 mt-5 mb-2">Otros impuestos <span class="font-normal text-slate-400">(opcionales, se suman al ITBIS)</span></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($addonTaxes as $key => $tax)
                        <x-ui.forms.checkbox label="{{ $tax['label'] }}" name="tax_keys[]" id="tax_key_{{ $key }}"
                            value="{{ $key }}" :checked="in_array($key, $oldTaxKeys)" />
                    @endforeach
                </div>
            @endif
            @error('tax_keys')
                <p class="mt-2 text-xs text-state-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Calculadora de ITBIS (REQ-1.4): solo ayuda visual, no se guarda nada. --}}
        <div class="mt-6" x-data="{
                open: false,
                mode: 'remove',
                amount: '',
                get base() {
                    const a = parseFloat(this.amount) || 0;
                    return this.mode === 'add' ? a : a / (1 + rate / 100);
                },
                get total() {
                    const a = parseFloat(this.amount) || 0;
                    return this.mode === 'add' ? a * (1 + rate / 100) : a;
                },
                get tax() { return this.total - this.base; },
                money(v) { return v.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            }">
            <div class="rounded-xl border border-gray-200 bg-gray-50/60">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                    <span class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <x-heroicon-o-calculator class="h-5 w-5 text-gray-400" />
                        Calculadora de ITBIS
                        <span class="text-xs font-normal text-gray-500" x-text="'Tasa marcada: ' + rate + '%'"></span>
                    </span>
                    <x-heroicon-s-chevron-down class="h-4 w-4 text-gray-400 transition-transform" ::class="open && 'rotate-180'" />
                </button>

                <div x-show="open" x-transition.opacity x-cloak class="border-t border-gray-200 px-4 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-wrap gap-x-6 gap-y-2">
                                <x-ui.forms.radio label="Quitar ITBIS" name="itbis_calc_mode" id="itbis_calc_remove" value="remove"
                                    x-model="mode" description="Tengo el precio final y quiero la base." />
                                <x-ui.forms.radio label="Agregar ITBIS" name="itbis_calc_mode" id="itbis_calc_add" value="add"
                                    x-model="mode" description="Tengo la base y quiero el precio final." />
                            </div>
                            <x-ui.forms.input label="Monto ({{ $symbol }})" name="itbis_calc_amount" type="number" step="0.01" min="0"
                                x-model="amount" placeholder="0.00"
                                hint="Usa las tasas de los impuestos marcados arriba." />
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between"><dt class="text-gray-500">Base (sin impuesto)</dt><dd class="font-medium text-gray-900 tabular-nums" x-text="money(base)"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500" x-text="'Impuesto (' + rate + '%)'"></dt><dd class="font-medium text-gray-900 tabular-nums" x-text="money(tax)"></dd></div>
                                <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="font-semibold text-gray-900">Total</dt><dd class="font-semibold text-gray-900 tabular-nums" x-text="money(total)"></dd></div>
                            </dl>
                            <x-ui.button type="button" variant="secondary" appearance="outline" size="sm" class="mt-4 w-full justify-center"
                                iconLeft="heroicon-s-arrow-up-tray"
                                x-bind:disabled="!(parseFloat(amount) > 0)"
                                x-on:click="document.getElementById('price').value = base.toFixed(2)">
                                Usar la base como precio de venta
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.infolist.tab>

    {{-- PESTAÑA 3: Inventario (REQ-1.3) — solo productos; la pestaña se oculta con isService --}}
    <x-ui.infolist.tab name="inventario">
        @if($isEdit)
                <p class="text-sm text-gray-500">La existencia no se cambia aquí: se ajusta con una toma física, una compra o una merma. Sí puedes cambiar el mínimo y el máximo de cada almacén.</p>
                @if($product->stocks->isEmpty())
                    <p class="text-sm text-gray-500">Este producto todavía no tiene existencias en ningún almacén.</p>
                @else
                    <div class="flex flex-col divide-y divide-gray-100">
                        @foreach($product->stocks as $stock)
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_8rem_10rem_10rem] gap-x-6 gap-y-3 py-4 first:pt-0 last:pb-0 sm:items-end">
                                <div class="min-w-0">
                                    <span class="block text-xs font-semibold text-slate-600 mb-1.5">Almacén</span>
                                    <span class="block truncate text-sm font-medium text-gray-900">{{ $stock->warehouse?->name ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-600 mb-1.5">Existencia</span>
                                    <span class="block text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($stock->quantity, 2) }}</span>
                                </div>
                                <x-ui.forms.input label="Mínimo" name="stocks[{{ $stock->id }}][min_stock]" id="stock_{{ $stock->id }}_min"
                                    type="number" step="0.01" min="0"
                                    value="{{ old('stocks.'.$stock->id.'.min_stock', (float) $stock->min_stock) }}"
                                    :error="$errors->first('stocks.'.$stock->id.'.min_stock')" />
                                <x-ui.forms.input label="Máximo" name="stocks[{{ $stock->id }}][max_stock]" id="stock_{{ $stock->id }}_max"
                                    type="number" step="0.01" min="0" placeholder="Sin tope"
                                    value="{{ old('stocks.'.$stock->id.'.max_stock', $stock->max_stock !== null ? (float) $stock->max_stock : '') }}"
                                    :error="$errors->first('stocks.'.$stock->id.'.max_stock')" />
                            </div>
                        @endforeach
                    </div>
                @endif
        @else
                <p class="text-sm text-gray-500">Cuántas unidades hay hoy y en qué almacén, para no tener que hacer una toma física después.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-5">
                    <x-ui.forms.select label="Almacén" name="inventory[warehouse_id]" id="inventory_warehouse_id" placeholder=""
                        :error="$errors->first('inventory.warehouse_id')">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('inventory.warehouse_id', $warehouses->first()?->id) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    @if($tracking)
                        <x-ui.forms.input label="Cantidad inicial" name="inventory[quantity]" id="inventory_quantity"
                            type="number" step="0.01" min="0" placeholder="0"
                            value="{{ old('inventory.quantity') }}"
                            hint="Queda en el kardex como inventario inicial, al costo del producto."
                            :error="$errors->first('inventory.quantity')" />
                    @endif

                    <x-ui.forms.input label="Mínimo" name="inventory[min_stock]" id="inventory_min_stock"
                        type="number" step="0.01" min="0" placeholder="0"
                        value="{{ old('inventory.min_stock') }}"
                        hint="Por debajo, aparece como stock bajo."
                        :error="$errors->first('inventory.min_stock')" />

                    <x-ui.forms.input label="Máximo" name="inventory[max_stock]" id="inventory_max_stock"
                        type="number" step="0.01" min="0" placeholder="Sin tope"
                        value="{{ old('inventory.max_stock') }}"
                        hint="Opcional. Por encima, hay sobre stock."
                        :error="$errors->first('inventory.max_stock')" />
                </div>
        @endif
    </x-ui.infolist.tab>

</x-ui.infolist.tabs>
</div>
