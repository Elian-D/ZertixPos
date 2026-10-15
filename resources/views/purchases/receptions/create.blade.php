{{-- Recibir una orden de compra (v1.5.0 REQ-5.2 a REQ-5.4) — /filament-form, una columna:
     1. Entrega: fecha, factura y NCF del proveedor y, de contado, la cuenta del pago con su
        vista previa (se permite el negativo: la mercancía ya entró).
     2. Lo que llegó: por línea, recibir y rechazar (con motivo) sobre lo pendiente, y el costo
        de la factura (por defecto, el de la orden; avisa si cambia).
     3. Totales: lo que entra, lo rechazado y lo que se paga o se debe.
     Al confirmar aparece el modal omitible "¿Cómo fue este despacho?" (A–D).
     Recibe $order (vendor, warehouse, items.product), $accounts (activas, con saldo),
     $reasons y $ratings. --}}
@php
    $currency = config('regional.currency_symbol');
    $today = now()->toDateString();
    $oldLines = old('lines', []);
    $items = $order->items->filter(fn ($i) => $i->pending > 0)->map(fn ($i) => [
        'id' => $i->id,
        'name' => $i->product->name ?? 'Producto eliminado',
        'code' => collect([$i->product?->sku, $i->product?->barcode])->filter()->join(' · '),
        'ordered' => (float) $i->quantity,
        'pending' => $i->pending,
        'order_cost' => (float) $i->unit_cost,
        'received' => (string) ($oldLines[$i->id]['received'] ?? $i->pending),
        'rejected' => (string) ($oldLines[$i->id]['rejected'] ?? ''),
        'reason' => (string) ($oldLines[$i->id]['reason'] ?? ''),
        'unit_cost' => (string) ($oldLines[$i->id]['unit_cost'] ?? (float) $i->unit_cost),
    ])->values();
    $accountData = $accounts->mapWithKeys(fn ($a) => [$a->id => ['name' => $a->name, 'balance' => (float) $a->current_balance]]);
    $defaultAccount = $order->financial_account_id ?? $order->vendor?->default_financial_account_id;
@endphp

<x-app-layout :title="'Recibir '.$order->number">
    <div class="p-4 md:p-6"
        x-data="{
            items: @js($items),
            accounts: @js($accountData),
            accountId: @js((string) old('financial_account_id', $accountData->has($defaultAccount) ? $defaultAccount : '')),
            date: @js(old('reception_date', $today)),
            cash: @js($order->isCash()),
            creditDays: @js((int) ($order->vendor->credit_days ?? 0)),
            currency: @js($currency),
            rating: @js(old('rating', '')),
            ratingNote: @js(old('rating_note', '')),
            num(v) { const n = parseFloat(v); return isNaN(n) ? 0 : n; },
            over(i) { return Math.round((this.num(i.received) + this.num(i.rejected)) * 100) > Math.round(i.pending * 100); },
            needsReason(i) { return this.num(i.rejected) > 0; },
            costChanged(i) { return i.unit_cost !== '' && Math.abs(this.num(i.unit_cost) - i.order_cost) > 0.00001; },
            subtotal(i) { return Math.round(this.num(i.received) * this.num(i.unit_cost) * 100) / 100; },
            left(i) { return Math.max(0, Math.round((i.pending - this.num(i.received) - this.num(i.rejected)) * 100) / 100); },
            get unitsIn() { return this.items.reduce((s, i) => s + this.num(i.received), 0); },
            get unitsRejected() { return this.items.reduce((s, i) => s + this.num(i.rejected), 0); },
            get total() { return this.items.reduce((s, i) => s + this.subtotal(i), 0); },
            get nothing() { return this.unitsIn + this.unitsRejected <= 0; },
            get invalid() { return this.nothing || this.items.some(i => this.over(i)); },
            get account() { return this.accounts[this.accountId] || null; },
            get after() { return this.account ? Math.round((this.account.balance - this.total) * 100) / 100 : null; },
            get dueDate() {
                if (!this.date) return '';
                const d = new Date(this.date + 'T12:00:00');
                d.setDate(d.getDate() + this.creditDays);
                return d.toLocaleDateString('es-DO', { day: '2-digit', month: '2-digit', year: 'numeric' });
            },
            qty(v) { return Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }); },
            money(v) { return (v < 0 ? '−' : '') + this.currency + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            receiveAll() { this.items.forEach(i => { i.received = String(i.pending); i.rejected = ''; i.reason = ''; }); },
            clearAll() { this.items.forEach(i => { i.received = '0'; }); },
            // Primero la validación del navegador; después, el modal de calificación.
            confirm() {
                if (this.invalid || !this.$refs.form.reportValidity()) return;
                this.$dispatch('open-modal', 'rate-reception');
            },
            // Guard de doble envío. Se libera solo si la página no navegó en 20 s (envío
            // colgado) o si se vuelve con Atrás (el navegador restaura la página tal cual).
            sending: false,
            submit(withRating) {
                if (this.sending) return;
                this.sending = true;
                if (!withRating) { this.rating = ''; this.ratingNote = ''; }
                setTimeout(() => { this.sending = false; }, 20000);
                this.$nextTick(() => this.$refs.form.submit());
            },
        }"
        x-on:pageshow.window="sending = false">

        <x-ui.page-header :title="'Recibir '.$order->number"
            :description="($order->vendor?->display_name ?? 'Proveedor').' → '.($order->warehouse->name ?? '').' · '.$order->condition_label">
            <x-slot:actions>
                <x-ui.button href="{{ route('purchases.orders.show', $order) }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="POST" action="{{ route('purchases.orders.receive', $order) }}" class="mt-6 flex flex-col gap-6" x-ref="form" x-on:submit.prevent="confirm()">
            @csrf

            @error('lines')
                <x-ui.alert variant="error">{{ $message }}</x-ui.alert>
            @enderror

            {{-- Fila 1: la entrega --}}
            <x-ui.infolist.section title="Entrega" icon="heroicon-o-truck" :cols="0" description="Los datos de la factura que trae el proveedor.">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-5">
                    <x-ui.forms.input label="Fecha de recepción" name="reception_date" type="date" required max="{{ $today }}" x-model="date"
                        :error="$errors->first('reception_date')" />

                    <x-ui.forms.input label="Factura del proveedor" name="supplier_invoice" maxlength="50" :value="old('supplier_invoice')"
                        placeholder="Ej. FT-4589" hint="Opcional." :error="$errors->first('supplier_invoice')" />

                    <x-ui.forms.input label="NCF del proveedor" name="supplier_ncf" maxlength="19" :value="old('supplier_ncf')"
                        placeholder="Ej. B0100000123" hint="Opcional." :error="$errors->first('supplier_ncf')" />

                    <div class="sm:col-span-3">
                        <x-ui.forms.textarea label="Nota" name="notes" :rows="2" maxlength="500" placeholder="Opcional (ej. chofer, estado del despacho)"
                            :error="$errors->first('notes')">{{ old('notes') }}</x-ui.forms.textarea>
                    </div>
                </div>
            </x-ui.infolist.section>

            {{-- Fila 2: lo que llegó --}}
            <x-ui.infolist.section title="Lo que llegó" icon="heroicon-o-inbox-arrow-down" :cols="0"
                description="Viene propuesto todo lo pendiente. Lo rechazado no entra al inventario ni se paga, y cierra lo pendiente.">
                <div class="mb-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" size="sm" variant="secondary" appearance="outline" iconLeft="heroicon-s-check" x-on:click="receiveAll()">
                        Recibir todo lo pendiente
                    </x-ui.button>
                    <x-ui.button type="button" size="sm" variant="secondary" appearance="ghost" iconLeft="heroicon-s-arrow-uturn-left" x-on:click="clearAll()">
                        Poner lo recibido en 0
                    </x-ui.button>
                </div>

                <x-ui.infolist.repeater countExpr="items.length">
                    <template x-for="(item, idx) in items" :key="item.id">
                        <x-ui.infolist.repeater-item>
                            <x-slot:title>
                                <span class="block font-medium truncate" x-text="item.name"></span>
                                <span class="block text-xs text-gray-500">
                                    Pedido <span class="font-semibold tabular-nums" x-text="qty(item.ordered)"></span>
                                    · pendiente <span class="font-semibold tabular-nums text-gray-800" x-text="qty(item.pending)"></span>
                                    <span x-show="item.code"> · <span class="font-mono" x-text="item.code"></span></span>
                                </span>
                            </x-slot:title>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[8rem_8rem_1fr_10rem] gap-x-4 gap-y-4 sm:items-start">
                                <x-ui.forms.input label="Recibir" name="" type="number" step="0.01" min="0" placeholder="0"
                                    x-bind:name="'lines[' + item.id + '][received]'" x-bind:id="'line_' + item.id + '_received'"
                                    x-bind:max="item.pending" x-model="item.received" />

                                <x-ui.forms.input label="Rechazar" name="" type="number" step="0.01" min="0" placeholder="0"
                                    x-bind:name="'lines[' + item.id + '][rejected]'" x-bind:id="'line_' + item.id + '_rejected'"
                                    x-bind:max="item.pending" x-model="item.rejected" />

                                <div class="min-w-0">
                                    <x-ui.forms.select label="Motivo del rechazo" name="" x-bind:name="'lines[' + item.id + '][reason]'"
                                        x-bind:id="'line_' + item.id + '_reason'" x-model="item.reason"
                                        x-bind:required="needsReason(item)" x-bind:disabled="!needsReason(item)"
                                        placeholder="Solo si se rechaza algo">
                                        @foreach($reasons as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </x-ui.forms.select>
                                </div>

                                <div>
                                    <x-ui.forms.input label="Costo unitario" name="" type="number" step="0.01" min="0" :addonLeft="$currency"
                                        x-bind:name="'lines[' + item.id + '][unit_cost]'" x-bind:id="'line_' + item.id + '_cost'"
                                        x-model="item.unit_cost" required />
                                    <p class="mt-1.5 text-xs text-state-warning" x-show="costChanged(item)" x-cloak>
                                        Precio de la orden: <span class="font-semibold tabular-nums" x-text="money(item.order_cost)"></span>
                                    </p>
                                </div>
                            </div>

                            <p class="mt-3 text-xs">
                                <span class="text-state-error font-medium" x-show="over(item)">Recibido más rechazado supera lo pendiente (<span x-text="qty(item.pending)"></span>).</span>
                                <span class="text-gray-500" x-show="!over(item)">
                                    Subtotal <span class="font-semibold tabular-nums text-gray-800" x-text="money(subtotal(item))"></span>
                                    <span x-show="left(item) > 0"> · quedan <span class="font-semibold tabular-nums text-gray-800" x-text="qty(left(item))"></span> pendientes para otra recepción</span>
                                </span>
                            </p>
                        </x-ui.infolist.repeater-item>
                    </template>
                </x-ui.infolist.repeater>
            </x-ui.infolist.section>

            {{-- Fila 3: totales y pago --}}
            <x-ui.infolist.section :title="$order->isCash() ? 'Pago de contado' : 'Cuenta por pagar'" icon="heroicon-o-banknotes" :cols="0"
                :description="$order->isCash() ? 'Se paga ahora lo recibido, desde la cuenta que elijas.' : 'Nace la cuenta por pagar por lo recibido; se paga después.'">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Entra al inventario</span>
                        <span class="mt-1 block text-lg font-bold tabular-nums text-gray-900" x-text="qty(unitsIn) + ' u.'"></span>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Rechazado</span>
                        <span class="mt-1 block text-lg font-bold tabular-nums" :class="unitsRejected > 0 ? 'text-state-error' : 'text-gray-900'" x-text="qty(unitsRejected) + ' u.'"></span>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $order->isCash() ? 'A pagar' : 'A deber' }}</span>
                        <span class="mt-1 block text-lg font-bold tabular-nums text-gray-900" x-text="money(total)"></span>
                    </div>
                </div>

                @if($order->isCash())
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5" x-show="total > 0">
                        <div>
                            <x-ui.forms.select label="Cuenta" name="financial_account_id" x-model="accountId" placeholder="Seleccione la cuenta..."
                                x-bind:required="total > 0" :error="$errors->first('financial_account_id')">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    </div>

                    {{-- Vista previa: cómo queda la cuenta. El negativo se permite (decisión del 2026-10-14). --}}
                    <div class="mt-4 rounded-xl bg-gray-50 px-4 py-3 text-sm" x-show="account && total > 0" x-cloak>
                        <p class="flex flex-wrap items-center gap-x-2">
                            <span class="font-medium text-gray-700" x-text="account && account.name"></span>
                            <span class="tabular-nums text-gray-500" x-text="account && money(account.balance)"></span>
                            <span class="text-gray-400">→</span>
                            <span class="font-bold tabular-nums" :class="after < 0 ? 'text-state-warning' : 'text-gray-900'" x-text="after !== null && money(after)"></span>
                        </p>
                        <p class="mt-2 text-xs text-gray-500" x-show="after < 0">
                            La cuenta queda en negativo. Se recibe igual: después concilia la cuenta o registra el depósito que falta.
                        </p>
                    </div>
                @else
                    <p class="mt-4 text-xs text-gray-500" x-show="total > 0">
                        Vence el <span class="font-semibold text-gray-800" x-text="dueDate"></span>
                        ({{ (int) ($order->vendor->credit_days ?? 0) }} días de crédito del proveedor).
                    </p>
                @endif

                <p class="mt-4 text-xs text-gray-500" x-show="total <= 0 && unitsRejected > 0" x-cloak>
                    Solo se rechaza: no entra nada al inventario y no nace ninguna deuda. Queda la recepción como constancia.
                </p>
            </x-ui.infolist.section>

            {{-- Calificación del despacho (REQ-5.4): viaja con el formulario --}}
            <input type="hidden" name="rating" x-bind:value="rating">
            <input type="hidden" name="rating_note" x-bind:value="ratingNote">

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('purchases.orders.show', $order) }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                {{-- type="button": el envío real lo hace el modal (form.submit()), así el guard de
                     doble envío de x-ui.button no queda girando si cierran el modal. --}}
                <x-ui.button type="button" variant="primary" iconLeft="heroicon-s-check" x-bind:disabled="invalid" x-on:click="confirm()">
                    Confirmar recepción
                </x-ui.button>
            </div>
        </form>

        <x-modal name="rate-reception" maxWidth="lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900">¿Cómo fue este despacho?</h3>
                <p class="mt-1 text-sm text-gray-500">Opcional. Con las calificaciones se arma la del proveedor.</p>

                <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($ratings as $grade => $label)
                        <button type="button" x-on:click="rating = @js($grade)"
                            class="rounded-xl border px-3 py-3 text-center transition-colors"
                            :class="rating === @js($grade) ? 'border-zertix-primary bg-zertix-primary/5' : 'border-gray-200 hover:bg-gray-50'">
                            <span class="block text-xl font-bold text-gray-900">{{ $grade }}</span>
                            <span class="block text-xs text-gray-500">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4" x-show="rating" x-cloak>
                    <x-ui.forms.input label="Nota" name="rating_note_input" x-model="ratingNote" maxlength="255"
                        placeholder="Opcional (ej. llegó tarde, buen empaque)" />
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <x-ui.button type="button" appearance="ghost" variant="secondary" x-bind:disabled="sending" x-on:click="submit(false)">
                        Omitir y recibir
                    </x-ui.button>
                    <x-ui.button type="button" variant="primary" iconLeft="heroicon-s-check" x-bind:disabled="!rating || sending" x-on:click="submit(true)">
                        Calificar y recibir
                    </x-ui.button>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
