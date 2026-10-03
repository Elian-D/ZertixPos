{{-- Ajuste manual de inventario (v1.5.0 REQ-1.5) — /filament-form, una columna y dos filas:
     datos del ajuste y líneas. Cada línea se guarda como un movimiento 'adjustment_in' o
     'adjustment_out' del kardex, con el motivo y el comentario en la descripción.
     Recibe: $warehouses, $products (solo tipo producto, activos), $stocks
     ([warehouse_id => [product_id => cantidad]]) y $reasons. --}}
@php
    $oldLines = old('lines', [['product_id' => '', 'direction' => 'in', 'quantity' => '']]);
@endphp

<x-app-layout title="Ajuste de inventario">
    <div class="p-4 md:p-6"
        x-data="{
            warehouseId: @js((string) old('warehouse_id', $warehouses->first()?->id)),
            stocks: @js($stocks),
            products: @js($products->keyBy('id')),
            lines: @js(array_values($oldLines)),
            errors: @js($errors->getMessages()),
            addLine() { this.lines.push({ product_id: '', direction: 'in', quantity: '' }); },
            removeLine(i) { this.lines.splice(i, 1); },
            current(line) { return (this.stocks[this.warehouseId] || {})[line.product_id] ?? 0; },
            result(line) {
                const q = parseFloat(line.quantity) || 0;
                return this.current(line) + (line.direction === 'in' ? q : -q);
            },
            qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
            err(i, field) { return (this.errors['lines.' + i + '.' + field] || [])[0] || ''; },
            // Resumen de la línea en su encabezado (se ve también contraída).
            summary(line) {
                const p = this.products[line.product_id];
                if (!p) return 'Nueva línea';
                const q = parseFloat(line.quantity) || 0;
                return (line.direction === 'in' ? 'Entrada' : 'Salida') + ' · ' + p.name + (q ? ' × ' + this.qty(q) : '');
            },
        }">

        <x-ui.page-header title="Ajuste de inventario"
            description="Agrega o saca existencias a mano para corregir un error que ningún documento puede anular. Queda en el kardex con tu usuario, el motivo y el comentario." />

        <form method="POST" action="{{ route('inventory.movements.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf

            {{-- Fila 1: datos del ajuste --}}
            <x-ui.infolist.section title="Datos del ajuste" icon="heroicon-o-adjustments-horizontal" :cols="0"
                description="En qué almacén y por qué se corrige.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.select label="Almacén" name="warehouse_id" x-model="warehouseId" placeholder="" required
                        hint="Las existencias de cada línea se muestran para este almacén."
                        :error="$errors->first('warehouse_id')">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <x-ui.forms.select label="Motivo" name="reason" placeholder="Seleccione el motivo..." required
                        :error="$errors->first('reason')">
                        @foreach($reasons as $key => $label)
                            <option value="{{ $key }}" @selected(old('reason') === $key)>{{ $label }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <div class="sm:col-span-2">
                        <x-ui.forms.textarea label="Comentario" name="notes" :rows="3" required
                            placeholder="Ej: La venta VTA-000120 se registró con 10 unidades y se entregaron 8."
                            hint="Explica qué pasó: queda en cada movimiento del kardex. Máximo 200 caracteres."
                            maxlength="200"
                            :error="$errors->first('notes')">{{ old('notes') }}</x-ui.forms.textarea>
                    </div>
                </div>
            </x-ui.infolist.section>

            {{-- Fila 2: líneas --}}
            <x-ui.infolist.section title="Líneas" icon="heroicon-o-queue-list" :cols="0"
                description="Cada línea suma (entrada) o resta (salida) existencias de un producto.">
                @error('lines')
                    <div class="mb-4 rounded-lg border border-state-error/20 bg-state-error/5 px-4 py-3 text-sm text-state-error">{{ $message }}</div>
                @enderror

                <x-ui.infolist.repeater countExpr="lines.length" addAction="addLine()">
                    <template x-for="(line, i) in lines" :key="i">
                        <x-ui.infolist.repeater-item removeAction="removeLine(i)" removeDisabled="lines.length === 1">
                            <x-slot:title>
                                <span x-text="summary(line)"></span>
                            </x-slot:title>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[1fr_auto_9rem] gap-x-4 gap-y-4 sm:items-start">
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

                                {{-- Dos opciones: radio en vez de selector (un toque en móvil) --}}
                                <div>
                                    <span class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo <span class="text-state-error">*</span></span>
                                    <div class="flex gap-x-5 gap-y-2 sm:pt-2.5">
                                        <x-ui.forms.radio label="Entrada (+)" value="in" x-model="line.direction"
                                            x-bind:name="'lines[' + i + '][direction]'" x-bind:id="'line_' + i + '_in'" />
                                        <x-ui.forms.radio label="Salida (−)" value="out" x-model="line.direction"
                                            x-bind:name="'lines[' + i + '][direction]'" x-bind:id="'line_' + i + '_out'" />
                                    </div>
                                </div>

                                <div>
                                    <x-ui.forms.input label="Cantidad" name="" type="number" step="0.01" min="0.01" placeholder="0"
                                        x-bind:name="'lines[' + i + '][quantity]'" x-bind:id="'line_' + i + '_quantity'"
                                        x-model="line.quantity" required />
                                    <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'quantity')" x-text="err(i, 'quantity')"></p>
                                </div>
                            </div>

                            {{-- Existencia en el almacén elegido: actual → la que queda --}}
                            <p class="mt-3 text-xs text-gray-500" x-show="line.product_id">
                                Existencia: <span class="font-semibold text-gray-800 tabular-nums" x-text="qty(current(line))"></span>
                                → queda <span class="font-semibold tabular-nums" :class="result(line) < 0 ? 'text-state-error' : 'text-gray-800'" x-text="qty(result(line))"></span>
                                <span class="text-state-error" x-show="result(line) < 0">· no alcanza la existencia</span>
                            </p>
                        </x-ui.infolist.repeater-item>
                    </template>
                </x-ui.infolist.repeater>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.movements.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Aplicar ajuste
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
