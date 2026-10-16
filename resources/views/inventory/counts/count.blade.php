{{-- Pantalla de conteo de una toma física en borrador (v1.5.0 REQ-2.1) — /filament-form.
     Buscador por nombre, SKU o código de barras; un escaneo suma 1 al producto
     (resources/js/utils/barcode-scanner.js). Con conteo ciego no se muestra la existencia
     del sistema. "Guardar avance" envía las cantidades empaquetadas en un solo campo JSON
     (SaveInventoryCountRequest explica por qué). Recibe $count con items.product. --}}
@php
    // El contador sin permiso de ver vuelve al listado, no al show.
    $backUrl = auth()->user()->can('inventory_counts.view')
        ? route('inventory.counts.show', $count)
        : route('inventory.counts.index');
    $items = $count->items->map(fn ($i) => [
        'id' => $i->id,
        'name' => $i->product->name ?? 'Producto eliminado',
        'sku' => $i->product->sku,
        'barcode' => $i->product->barcode,
        // Conteo ciego: la existencia del sistema ni siquiera viaja a la página (no se
        // puede ver con las herramientas del navegador).
        'system' => $count->blind ? null : (float) $i->system_quantity,
        'counted' => $i->counted_quantity !== null ? (string) (float) $i->counted_quantity : '',
    ])->values();
@endphp

<x-app-layout :title="'Contar '.$count->number">
    <div class="p-4 md:p-6"
        x-data="{
            items: @js($items),
            blind: @js($count->blind),
            search: '',
            onlyPending: false,
            // El filtro Solo sin contar fija la lista al activarlo: si mirara el valor en vivo,
            // la fila desaparecería al escribir el primer dígito. Se refresca cada vez que se
            // vuelve a activar. (Sin comillas dobles aquí: cerrarían el atributo x-data.)
            pendingIds: {},
            togglePending() {
                this.pendingIds = Object.fromEntries(this.items.filter(i => i.counted === '').map(i => [i.id, true]));
            },
            dirty: false,
            flashId: null,
            get filtered() {
                const t = this.search.trim().toLowerCase();
                return this.items.filter(i => {
                    if (this.onlyPending && !this.pendingIds[i.id]) return false;
                    if (!t) return true;
                    return i.name.toLowerCase().includes(t)
                        || (i.sku || '').toLowerCase().includes(t)
                        || (i.barcode || '').toLowerCase().includes(t);
                });
            },
            get countedTotal() { return this.items.filter(i => i.counted !== '').length; },
            get payload() {
                return JSON.stringify(Object.fromEntries(this.items.map(i => [i.id, i.counted === '' ? null : i.counted])));
            },
            diff(i) { return i.counted === '' ? null : (parseFloat(i.counted) || 0) - i.system; },
            qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
            // Un escaneo con coincidencia exacta (código de barras, luego SKU) suma 1.
            scan(code) {
                const t = code.trim().toLowerCase();
                if (!t) return false;
                const item = this.items.find(i => (i.barcode || '').toLowerCase() === t)
                    || this.items.find(i => (i.sku || '').toLowerCase() === t);
                if (!item) return false;
                item.counted = String((parseFloat(item.counted) || 0) + 1);
                this.dirty = true;
                this.search = '';
                this.flashId = item.id;
                setTimeout(() => { if (this.flashId === item.id) this.flashId = null; }, 900);
                return true;
            },
            init() {
                window.__countScanner?.destroy();
                window.__countScanner = window.ZertixScanner?.create({
                    inputs: [this.$refs.search],
                    onScan: (code) => this.scan(code),
                    onMiss: (code) => { this.search = code; this.$refs.search.focus(); },
                });
                // Aviso al salir con cantidades sin guardar.
                window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
            },
        }">

        <x-ui.page-header :title="'Contar '.$count->number"
            :description="$count->warehouse->name.' · '.$count->scope_label.($count->blind ? ' · Conteo ciego' : '')">
            <x-slot:actions>
                <x-ui.button href="{{ $backUrl }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="POST" action="{{ route('inventory.counts.save', $count) }}" class="mt-6 flex flex-col gap-6"
            @submit="dirty = false">
            @csrf
            @method('PUT')
            <input type="hidden" name="counts" :value="payload">

            <x-ui.alert variant="neutral" title="Cuenta cada producto antes de venderlo.">
                Se compara con la existencia del {{ $count->created_at->format('d/m/Y h:i A') }}.
            </x-ui.alert>

            <x-ui.infolist.section title="Conteo" icon="heroicon-o-clipboard-document-list" :cols="0"
                description="Escribe la cantidad contada o escanea: cada lectura suma 1. Los productos sin contar no generan ajuste.">
                <x-slot:headerActions>
                    {{-- Conteo ciego: indicador discreto, sin banner (no debe sentirse como una advertencia). --}}
                    @if($count->blind)
                        <x-ui.badge variant="slate" size="sm" icon="heroicon-s-eye-slash"
                            title="La existencia del sistema no se muestra en este conteo">
                            Conteo ciego
                        </x-ui.badge>
                    @endif
                    <x-ui.badge variant="info" size="sm" :dot="false">
                        <span x-text="countedTotal + ' de ' + items.length + ' contados'"></span>
                    </x-ui.badge>
                </x-slot:headerActions>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-4 sm:items-end mb-4">
                    <x-ui.forms.input name="count_search" placeholder="Busca por nombre, SKU o código de barras, o escanea…"
                        iconLeft="heroicon-o-magnifying-glass" autocomplete="off" x-model="search" x-ref="search"
                        x-on:keydown.enter.prevent="scan(search)" />
                    <div class="sm:pb-2.5">
                        <x-ui.forms.checkbox label="Solo sin contar" name="" id="only_pending" x-model="onlyPending"
                            x-on:change="togglePending()" />
                    </div>
                </div>

                <x-ui.infolist.repeater>
                    <template x-for="item in filtered" :key="item.id">
                        <x-ui.infolist.repeater-item compact
                            x-bind:class="flashId === item.id && '!border-zertix-primary !bg-zertix-primary/5'">
                            <x-slot:title>
                                <span class="block font-medium truncate" x-text="item.name"></span>
                                <span class="block text-xs text-gray-400 font-mono truncate"
                                    x-text="[item.sku, item.barcode].filter(Boolean).join(' · ')"></span>
                            </x-slot:title>

                            <template x-if="!blind">
                                <span class="text-xs text-gray-500 whitespace-nowrap">
                                    Sistema <span class="font-semibold text-gray-800 tabular-nums" x-text="qty(item.system)"></span>
                                </span>
                            </template>

                            <div class="w-28">
                                <x-ui.forms.input name="" type="number" step="0.01" min="0" placeholder="Sin contar"
                                    x-model="item.counted" x-on:input="dirty = true"
                                    x-bind:aria-label="'Cantidad contada de ' + item.name" />
                            </div>

                            <template x-if="!blind">
                                <span class="w-16 text-right text-xs font-semibold tabular-nums"
                                    :class="diff(item) === null || diff(item) === 0 ? 'text-gray-400' : (diff(item) < 0 ? 'text-state-error' : 'text-emerald-600')"
                                    x-text="diff(item) === null ? '—' : (diff(item) > 0 ? '+' : '') + qty(diff(item))"></span>
                            </template>
                        </x-ui.infolist.repeater-item>
                    </template>

                    <p class="py-6 text-center text-sm text-gray-500" x-show="filtered.length === 0">
                        Ningún producto coincide con la búsqueda.
                    </p>
                </x-ui.infolist.repeater>
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Notas" icon="heroicon-o-chat-bubble-left-ellipsis" :cols="0">
                <x-ui.forms.textarea name="notes" :rows="2" placeholder="Observaciones del conteo (opcional)"
                    x-on:input="dirty = true"
                    :error="$errors->first('notes')">{{ old('notes', $count->notes) }}</x-ui.forms.textarea>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ $backUrl }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" name="then" value="count" variant="secondary" appearance="outline" iconLeft="heroicon-s-cloud-arrow-up">
                    Guardar avance
                </x-ui.button>
                {{-- El contador sin permiso de ver no entra a la revisión: termina y vuelve al listado. --}}
                @can('inventory_counts.view')
                    <x-ui.button type="submit" name="then" value="review" variant="primary" iconLeft="heroicon-s-check">
                        Guardar y revisar
                    </x-ui.button>
                @else
                    <x-ui.button type="submit" name="then" value="done" variant="primary" iconLeft="heroicon-s-check">
                        Guardar y terminar
                    </x-ui.button>
                @endcan
            </div>
        </form>
    </div>
</x-app-layout>
