{{-- Formulario de orden de compra (v1.5.0 REQ-5.1) — /filament-form, una columna y tres filas:
     1. Datos de la orden: proveedor, almacén, fechas, condición y cuenta sugerida.
     2. Líneas (x-ui.infolist.repeater): producto, cantidad, costo unitario y subtotal.
     3. Totales: líneas, unidades y total.
     Al elegir el proveedor se proponen su condición y su cuenta; al elegir un producto, su costo.
     Lo usan create y edit sin cambios.
     Recibe: $vendors, $warehouses, $accounts, $conditions, $products (tipo producto, activos,
     con costo) y $selectedVendor (?vendor=, opcional). Opcional: $order (edit, con items). --}}
@php
    $currency = config('regional.currency_symbol');
    $isEdit = isset($order);
    $defaultLines = $isEdit
        ? $order->items->map(fn ($i) => ['product_id' => (string) $i->product_id, 'quantity' => (string) (float) $i->quantity, 'unit_cost' => (string) (float) $i->unit_cost])->all()
        : [['product_id' => '', 'quantity' => '', 'unit_cost' => '']];
    $oldLines = old('lines', $defaultLines);
    $vendorData = $vendors->mapWithKeys(fn ($v) => [$v->id => ['condition' => $v->payment_condition, 'days' => (int) $v->credit_days, 'account' => $v->default_financial_account_id]]);
    $productData = $products->mapWithKeys(fn ($p) => [$p->id => ['name' => $p->name, 'cost' => (float) $p->cost]]);
@endphp

<div class="flex flex-col gap-6"
    x-data="{
        vendors: @js($vendorData),
        products: @js($productData),
        accountIds: @js($accounts->pluck('id')->map(fn ($id) => (string) $id)),
        vendorId: @js((string) old('vendor_id', $order->vendor_id ?? $selectedVendor ?? '')),
        condition: @js(old('payment_condition', $order->payment_condition ?? 'cash')),
        accountId: @js((string) old('financial_account_id', $order->financial_account_id ?? '')),
        lines: @js(array_values($oldLines)),
        errors: @js($errors->getMessages()),
        currency: @js($currency),
        init() {
            if (this.vendorId && !@js($isEdit || (bool) old('payment_condition'))) this.applyVendor();
            this.$watch('vendorId', () => this.applyVendor());
        },
        // El proveedor propone su condición de pago y su cuenta sugerida.
        applyVendor() {
            const v = this.vendors[this.vendorId];
            if (!v) return;
            this.condition = v.condition;
            if (v.account && this.accountIds.includes(String(v.account))) this.accountId = String(v.account);
        },
        get vendor() { return this.vendors[this.vendorId] || null },
        // Al elegir un producto, su costo actual como punto de partida (lo cotizado manda).
        pickProduct(line) {
            const p = this.products[line.product_id];
            if (p && (line.unit_cost === '' || line.unit_cost === null)) line.unit_cost = String(p.cost);
        },
        addLine() { this.lines.push({ product_id: '', quantity: '', unit_cost: '' }); },
        removeLine(i) { this.lines.splice(i, 1); },
        subtotal(line) { return Math.round((parseFloat(line.quantity) || 0) * (parseFloat(line.unit_cost) || 0) * 100) / 100; },
        get units() { return this.lines.reduce((s, l) => s + (parseFloat(l.quantity) || 0), 0); },
        get total() { return this.lines.reduce((s, l) => s + this.subtotal(l), 0); },
        qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
        money(v) { return this.currency + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        err(i, field) { return (this.errors['lines.' + i + '.' + field] || [])[0] || ''; },
        summary(line) {
            const p = this.products[line.product_id];
            if (!p) return 'Nueva línea';
            const q = parseFloat(line.quantity) || 0;
            return p.name + (q ? ' × ' + this.qty(q) + ' · ' + this.money(this.subtotal(line)) : '');
        },
    }">

    {{-- Fila 1: datos de la orden --}}
    <x-ui.infolist.section title="Datos de la orden" icon="heroicon-o-clipboard-document-list" :cols="0"
        description="A quién se le pide, a qué almacén entra y cómo se paga.">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <x-ui.forms.select label="Proveedor" name="vendor_id" x-model="vendorId" placeholder="Seleccione el proveedor..." required
                hint="Propone su condición de pago y su cuenta sugerida."
                :error="$errors->first('vendor_id')">
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->display_name }}</option>
                @endforeach
            </x-ui.forms.select>

            <x-ui.forms.select label="Almacén de destino" name="warehouse_id" placeholder="Seleccione el almacén..." required
                hint="Aquí entra la mercancía al recibirla."
                :error="$errors->first('warehouse_id')">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $order->warehouse_id ?? ($warehouses->count() === 1 ? $warehouse->id : '')) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </x-ui.forms.select>

            <x-ui.forms.input label="Fecha" name="order_date" type="date" required
                :value="old('order_date', $isEdit ? $order->order_date->toDateString() : now()->toDateString())"
                :error="$errors->first('order_date')" />

            <x-ui.forms.input label="Entrega estimada" name="expected_date" type="date"
                :value="old('expected_date', $isEdit ? $order->expected_date?->toDateString() : null)"
                hint="Opcional. Cuándo dijo el proveedor que llega."
                :error="$errors->first('expected_date')" />

            <div>
                <span class="block text-xs font-semibold text-slate-600 mb-1.5">Condición de pago <span class="text-state-error">*</span></span>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    @foreach($conditions as $value => $label)
                        <x-ui.forms.radio :label="$label" name="payment_condition" :value="$value" x-model="condition" />
                    @endforeach
                </div>
                <p class="mt-1.5 text-xs text-slate-400">
                    <span x-show="condition === 'cash'">Se paga al recibir, desde una cuenta de Cajas y Bancos.</span>
                    <span x-show="condition === 'credit'" x-cloak>Al recibir nace la cuenta por pagar<span x-show="vendor && vendor.days"> a <span x-text="vendor && vendor.days"></span> días</span>.</span>
                </p>
                @error('payment_condition')
                    <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="condition === 'cash'">
                <x-ui.forms.select label="Cuenta de pago sugerida" name="financial_account_id" x-model="accountId" placeholder="Elegir al recibir"
                    x-bind:disabled="condition !== 'cash'" hint="Opcional. Se puede cambiar al recibir."
                    :error="$errors->first('financial_account_id')">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>

            <div class="sm:col-span-2">
                <x-ui.forms.textarea label="Nota" name="notes" :rows="2" maxlength="500" placeholder="Opcional (ej. condiciones de entrega)"
                    :error="$errors->first('notes')">{{ old('notes', $order->notes ?? '') }}</x-ui.forms.textarea>
            </div>
        </div>
    </x-ui.infolist.section>

    {{-- Fila 2: líneas --}}
    <x-ui.infolist.section title="Líneas" icon="heroicon-o-queue-list" :cols="0"
        description="El costo es lo que el proveedor cobra por unidad, según su cotización.">
        @error('lines')
            <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
        @enderror

        <x-ui.infolist.repeater countExpr="lines.length" addAction="addLine()">
            <template x-for="(line, i) in lines" :key="i">
                <x-ui.infolist.repeater-item removeAction="removeLine(i)" removeDisabled="lines.length === 1">
                    <x-slot:title>
                        <span x-text="summary(line)"></span>
                    </x-slot:title>

                    <div class="grid grid-cols-1 sm:grid-cols-3 xl:grid-cols-[1fr_8rem_9rem_9rem] gap-x-4 gap-y-4 sm:items-start">
                        <div class="min-w-0 sm:col-span-3 xl:col-span-1">
                            <x-ui.forms.select label="Producto" name="" x-bind:name="'lines[' + i + '][product_id]'"
                                x-bind:id="'line_' + i + '_product'" x-model="line.product_id" x-on:change="pickProduct(line)"
                                placeholder="Seleccione un producto..." required>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}</option>
                                @endforeach
                            </x-ui.forms.select>
                            <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'product_id')" x-text="err(i, 'product_id')"></p>
                        </div>

                        <div>
                            <x-ui.forms.input label="Cantidad" name="" type="number" step="0.01" min="0.01" placeholder="0"
                                x-bind:name="'lines[' + i + '][quantity]'" x-bind:id="'line_' + i + '_quantity'"
                                x-model="line.quantity" required />
                            <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'quantity')" x-text="err(i, 'quantity')"></p>
                        </div>

                        <div>
                            <x-ui.forms.input label="Costo unitario" name="" type="number" step="0.01" min="0" placeholder="0.00"
                                :addonLeft="$currency"
                                x-bind:name="'lines[' + i + '][unit_cost]'" x-bind:id="'line_' + i + '_cost'"
                                x-model="line.unit_cost" required />
                            <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'unit_cost')" x-text="err(i, 'unit_cost')"></p>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-slate-600 mb-1.5">Subtotal</span>
                            <p class="h-10 flex items-center text-sm font-bold tabular-nums text-gray-900" x-text="money(subtotal(line))"></p>
                        </div>
                    </div>
                </x-ui.infolist.repeater-item>
            </template>
        </x-ui.infolist.repeater>
    </x-ui.infolist.section>

    {{-- Fila 3: totales --}}
    <x-ui.infolist.section title="Totales" icon="heroicon-o-calculator" :cols="0">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Líneas</span>
                <span class="mt-1 block text-lg font-bold tabular-nums text-gray-900" x-text="lines.length"></span>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Unidades</span>
                <span class="mt-1 block text-lg font-bold tabular-nums text-gray-900" x-text="qty(units)"></span>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Total</span>
                <span class="mt-1 block text-lg font-bold tabular-nums text-gray-900" x-text="money(total)"></span>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Se guarda en borrador. La deuda nace al recibir: se debe lo que llegó, no lo que se pidió.</p>
    </x-ui.infolist.section>
</div>
