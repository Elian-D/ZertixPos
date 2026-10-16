{{-- Formulario de transferencia (v1.5.0 REQ-2.4) — /filament-form, una columna y dos filas:
     Desde / Hacia y líneas (x-ui.infolist.repeater). Lo usan create y edit sin cambios.
     Recibe: $warehouses, $products (tipo producto, activos), $stocks
     ([warehouse_id => [product_id => cantidad]]). Opcional: $transfer (edit, con items). --}}
@php
    $isEdit = isset($transfer);
    $defaultLines = $isEdit
        ? $transfer->items->map(fn ($i) => ['product_id' => (string) $i->product_id, 'quantity' => (string) (float) $i->quantity_sent, 'notes' => $i->notes ?? ''])->all()
        : [['product_id' => '', 'quantity' => '', 'notes' => '']];
    $oldLines = old('lines', $defaultLines);
@endphp

<div class="flex flex-col gap-6"
    x-data="{
        fromId: @js((string) old('from_warehouse_id', $transfer->from_warehouse_id ?? '')),
        toId: @js((string) old('to_warehouse_id', $transfer->to_warehouse_id ?? '')),
        stocks: @js($stocks),
        products: @js($products->keyBy('id')),
        lines: @js(array_values($oldLines)),
        errors: @js($errors->getMessages()),
        addLine() { this.lines.push({ product_id: '', quantity: '', notes: '' }); },
        removeLine(i) { this.lines.splice(i, 1); },
        available(line) { return (this.stocks[this.fromId] || {})[line.product_id] ?? 0; },
        exceeds(line) { return (parseFloat(line.quantity) || 0) > this.available(line); },
        qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
        err(i, field) { return (this.errors['lines.' + i + '.' + field] || [])[0] || ''; },
        summary(line) {
            const p = this.products[line.product_id];
            if (!p) return 'Nueva línea';
            const q = parseFloat(line.quantity) || 0;
            return p.name + (q ? ' × ' + this.qty(q) : '');
        },
    }">

    {{-- Fila 1: Desde / Hacia --}}
    <x-ui.infolist.section title="Desde / Hacia" icon="heroicon-o-arrows-right-left" :cols="0">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <x-ui.forms.select label="Desde (origen)" name="from_warehouse_id" x-model="fromId" placeholder="Seleccione el almacén..." required
                hint="La existencia de cada línea es la de este almacén."
                :error="$errors->first('from_warehouse_id')">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </x-ui.forms.select>

            <x-ui.forms.select label="Hacia (destino)" name="to_warehouse_id" x-model="toId" placeholder="Seleccione el almacén..." required
                :error="$errors->first('to_warehouse_id')">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" x-bind:disabled="fromId === '{{ $warehouse->id }}'">{{ $warehouse->name }}</option>
                @endforeach
            </x-ui.forms.select>

            <div class="sm:col-span-2">
                <x-ui.forms.textarea label="Nota" name="notes" :rows="2" placeholder="Opcional (ej. chofer, vehículo)"
                    :error="$errors->first('notes')">{{ old('notes', $transfer->notes ?? '') }}</x-ui.forms.textarea>
            </div>
        </div>
    </x-ui.infolist.section>

    {{-- Fila 2: líneas --}}
    <x-ui.infolist.section title="Líneas" icon="heroicon-o-queue-list" :cols="0">
        @error('lines')
            <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
        @enderror

        <x-ui.infolist.repeater countExpr="lines.length" addAction="addLine()">
            <template x-for="(line, i) in lines" :key="i">
                <x-ui.infolist.repeater-item removeAction="removeLine(i)" removeDisabled="lines.length === 1">
                    <x-slot:title>
                        <span x-text="summary(line)"></span>
                    </x-slot:title>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[1fr_9rem] gap-x-4 gap-y-4 sm:items-start">
                        <div class="min-w-0 sm:col-span-2 xl:col-span-1">
                            <x-ui.forms.select label="Producto" name="" x-bind:name="'lines[' + i + '][product_id]'"
                                x-bind:id="'line_' + i + '_product'" x-model="line.product_id"
                                placeholder="Seleccione un producto..." required>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}</option>
                                @endforeach
                            </x-ui.forms.select>
                            <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'product_id')" x-text="err(i, 'product_id')"></p>
                        </div>

                        <div>
                            <x-ui.forms.input label="Cantidad a enviar" name="" type="number" step="0.01" min="0.01" placeholder="0"
                                x-bind:name="'lines[' + i + '][quantity]'" x-bind:id="'line_' + i + '_quantity'"
                                x-model="line.quantity" required />
                            <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'quantity')" x-text="err(i, 'quantity')"></p>
                        </div>

                        <div class="sm:col-span-2">
                            <x-ui.forms.input label="Nota de la línea" name="" placeholder="Opcional"
                                x-bind:name="'lines[' + i + '][notes]'" x-bind:id="'line_' + i + '_notes'"
                                x-model="line.notes" maxlength="255" />
                        </div>
                    </div>

                    {{-- Existencia en el origen: la cantidad no puede superarla al enviar --}}
                    <p class="mt-3 text-xs text-gray-500" x-show="line.product_id && fromId">
                        Existencia en el origen: <span class="font-semibold tabular-nums" :class="exceeds(line) ? 'text-state-error' : 'text-gray-800'" x-text="qty(available(line))"></span>
                        <span class="text-state-error" x-show="exceeds(line)">· la cantidad supera la existencia</span>
                    </p>
                </x-ui.infolist.repeater-item>
            </template>
        </x-ui.infolist.repeater>
    </x-ui.infolist.section>
</div>
