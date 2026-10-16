{{-- Registrar merma (v1.5.0 REQ-2.2) — /filament-form, una columna y dos filas: datos y
     líneas (x-ui.infolist.repeater). Se aplica al guardar: cada línea saca stock con un
     movimiento 'waste'. Recibe: $warehouses, $products (tipo producto, activos, con costo),
     $stocks ([warehouse_id => [product_id => cantidad]]) y $reasons (solo los manuales). --}}
@php
    $oldLines = old('lines', [['product_id' => '', 'quantity' => '', 'reason' => '', 'notes' => '']]);
    $currency = config('regional.currency_symbol');
@endphp

<x-app-layout title="Registrar merma">
    <div class="p-4 md:p-6"
        x-data="{
            warehouseId: @js((string) old('warehouse_id', '')),
            stocks: @js($stocks),
            products: @js($products->keyBy('id')),
            reasons: @js($reasons),
            lines: @js(array_values($oldLines)),
            errors: @js($errors->getMessages()),
            currency: @js($currency),
            addLine() { this.lines.push({ product_id: '', quantity: '', reason: '', notes: '' }); },
            removeLine(i) { this.lines.splice(i, 1); },
            current(line) { return (this.stocks[this.warehouseId] || {})[line.product_id] ?? 0; },
            exceeds(line) { return (parseFloat(line.quantity) || 0) > this.current(line); },
            value(line) {
                const p = this.products[line.product_id];
                return p ? (parseFloat(line.quantity) || 0) * parseFloat(p.cost || 0) : 0;
            },
            get total() { return this.lines.reduce((sum, l) => sum + this.value(l), 0); },
            qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
            money(v) { return this.currency + Number(v).toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            err(i, field) { return (this.errors['lines.' + i + '.' + field] || [])[0] || ''; },
            // Resumen de la línea en su encabezado (se ve también contraída).
            summary(line) {
                const p = this.products[line.product_id];
                if (!p) return 'Nueva línea';
                const q = parseFloat(line.quantity) || 0;
                return [this.reasons[line.reason], p.name + (q ? ' × ' + this.qty(q) : '')].filter(Boolean).join(' · ')
                    + (q ? ' · ' + this.money(this.value(line)) : '');
            },
        }">

        <x-ui.page-header title="Registrar merma"
            description="Da de baja mercancía dañada, vencida, robada o consumida. Se aplica al guardar." />

        <form method="POST" action="{{ route('inventory.wastes.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf

            {{-- Fila 1: datos --}}
            <x-ui.infolist.section title="Datos de la merma" icon="heroicon-o-archive-box-x-mark" :cols="0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.select label="Almacén" name="warehouse_id" x-model="warehouseId" placeholder="Seleccione el almacén..." required
                        :error="$errors->first('warehouse_id')">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <x-ui.forms.input label="Fecha" name="waste_date" type="date" required
                        value="{{ old('waste_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                        hint="Cuándo se detectó la pérdida."
                        :error="$errors->first('waste_date')" />

                    <div class="sm:col-span-2">
                        <x-ui.forms.textarea label="Nota" name="notes" :rows="2" placeholder="Opcional"
                            :error="$errors->first('notes')">{{ old('notes') }}</x-ui.forms.textarea>
                    </div>
                </div>
            </x-ui.infolist.section>

            {{-- Fila 2: líneas --}}
            <x-ui.infolist.section title="Líneas" icon="heroicon-o-queue-list" :cols="0">
                <x-slot:headerActions>
                    <span class="text-sm text-gray-500">Valor perdido <strong class="text-state-error tabular-nums" x-text="money(total)"></strong></span>
                </x-slot:headerActions>

                @error('lines')
                    <x-ui.alert variant="error" class="mb-4">{{ $message }}</x-ui.alert>
                @enderror

                <x-ui.infolist.repeater countExpr="lines.length" addAction="addLine()">
                    <template x-for="(line, i) in lines" :key="i">
                        <x-ui.infolist.repeater-item removeAction="removeLine(i)" removeDisabled="lines.length === 1">
                            <x-slot:title>
                                <span x-text="summary(line)"></span>
                            </x-slot:title>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[1fr_9rem_12rem] gap-x-4 gap-y-4 sm:items-start">
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
                                    <x-ui.forms.input label="Cantidad" name="" type="number" step="0.01" min="0.01" placeholder="0"
                                        x-bind:name="'lines[' + i + '][quantity]'" x-bind:id="'line_' + i + '_quantity'"
                                        x-model="line.quantity" required />
                                    <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'quantity')" x-text="err(i, 'quantity')"></p>
                                </div>

                                <div>
                                    <x-ui.forms.select label="Motivo" name="" x-bind:name="'lines[' + i + '][reason]'"
                                        x-bind:id="'line_' + i + '_reason'" x-model="line.reason"
                                        placeholder="Seleccione..." required>
                                        @foreach($reasons as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </x-ui.forms.select>
                                    <p class="mt-1.5 text-xs text-state-error break-words" x-show="err(i, 'reason')" x-text="err(i, 'reason')"></p>
                                </div>

                                <div class="sm:col-span-2 xl:col-span-3">
                                    <x-ui.forms.input label="Nota de la línea" name="" placeholder="Opcional (ej. lote, cómo se dañó)"
                                        x-bind:name="'lines[' + i + '][notes]'" x-bind:id="'line_' + i + '_notes'"
                                        x-model="line.notes" maxlength="255" />
                                </div>
                            </div>

                            {{-- Existencia en el almacén elegido y valor perdido de la línea --}}
                            <p class="mt-3 text-xs text-gray-500" x-show="line.product_id && warehouseId">
                                Existencia: <span class="font-semibold tabular-nums" :class="exceeds(line) ? 'text-state-error' : 'text-gray-800'" x-text="qty(current(line))"></span>
                                <span class="text-state-error" x-show="exceeds(line)">· la cantidad supera la existencia</span>
                                <span x-show="!exceeds(line) && line.quantity"> · se pierden <span class="font-semibold text-gray-800 tabular-nums" x-text="money(value(line))"></span> al costo</span>
                            </p>
                        </x-ui.infolist.repeater-item>
                    </template>
                </x-ui.infolist.repeater>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.wastes.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Registrar merma
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
