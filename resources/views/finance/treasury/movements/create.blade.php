{{-- Nuevo movimiento de Tesorería (v1.5.0 REQ-3.2) — /filament-form, mismo patrón que el
     ajuste de inventario: depósito, retiro o transferencia, con la vista previa del saldo en
     que queda cada cuenta. Ninguna cuenta puede quedar en negativo; el servicio lo valida
     igual con la cuenta bloqueada.
     Recibe: $accounts (activas, con saldo), $types y $selected (?account= desde el show). --}}
@php
    $currency = config('regional.currency_symbol');
    $balances = $accounts->mapWithKeys(fn ($a) => [$a->id => ['name' => $a->name, 'balance' => (float) $a->current_balance]]);
@endphp

<x-app-layout title="Nuevo movimiento">
    <div class="p-4 md:p-6"
        x-data="{
            accounts: @js($balances),
            accountId: @js((string) old('financial_account_id', $selected ?? '')),
            toId: @js((string) old('to_account_id', '')),
            type: @js(old('type', 'deposit')),
            amount: @js((string) old('amount', '')),
            get value() { const a = parseFloat(this.amount); return isNaN(a) ? 0 : a },
            get from() { return this.accounts[this.accountId] || null },
            get to() { return this.type === 'transfer' ? (this.accounts[this.toId] || null) : null },
            get fromAfter() {
                if (!this.from) return null;
                const sign = this.type === 'deposit' ? 1 : -1;
                return Math.round((this.from.balance + sign * this.value) * 100) / 100;
            },
            get toAfter() { return this.to ? Math.round((this.to.balance + this.value) * 100) / 100 : null },
            get invalid() { return this.fromAfter !== null && this.fromAfter < 0 },
            fmt(v) { return (v < 0 ? '−' : '') + @js($currency) + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
        }">

        <x-ui.page-header title="Nuevo movimiento"
            description="Entra, sale o se mueve dinero entre tus cajas y bancos. Queda en Movimientos de Tesorería con tu usuario y la nota." />

        <form method="POST" action="{{ route('finance.treasury.movements.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf

            @if($accounts->isEmpty())
                <x-ui.alert variant="neutral">
                    No hay cuentas activas. <a href="{{ route('finance.treasury.create') }}" class="font-semibold underline">Crea una cuenta</a> primero.
                </x-ui.alert>
            @endif

            {{-- Fila 1: qué movimiento --}}
            <x-ui.infolist.section title="Movimiento" icon="heroicon-o-arrows-right-left" :cols="0"
                description="De qué cuenta y qué tipo de movimiento.">
                <div class="flex flex-col gap-5">
                    <div>
                        <span class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo <span class="text-state-error">*</span></span>
                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            @foreach($types as $value => $label)
                                <x-ui.forms.radio :label="$label" name="type" :value="$value" x-model="type" />
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">
                            <span x-show="type === 'deposit'">Entra dinero a la cuenta: por ejemplo, el efectivo de la semana al banco.</span>
                            <span x-show="type === 'withdrawal'" x-cloak>Sale dinero de la cuenta: por ejemplo, un retiro del dueño.</span>
                            <span x-show="type === 'transfer'" x-cloak>Sale de una cuenta y entra a otra; el total no cambia.</span>
                        </p>
                        @error('type')<p class="mt-1.5 text-xs font-medium text-state-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                        <div>
                            <x-ui.forms.select name="financial_account_id" x-model="accountId" placeholder="Seleccione la cuenta..." required
                                label="Cuenta" hint="En una transferencia, la cuenta de la que sale." :error="$errors->first('financial_account_id')">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </x-ui.forms.select>
                            <p class="mt-1.5 text-xs text-slate-400" x-show="from">
                                Saldo actual: <span class="font-semibold text-slate-700 tabular-nums" x-text="from && fmt(from.balance)"></span>
                            </p>
                        </div>

                        <div x-show="type === 'transfer'" x-cloak>
                            <x-ui.forms.select label="Hacia" name="to_account_id" x-model="toId" placeholder="Seleccione la cuenta..."
                                x-bind:required="type === 'transfer'" x-bind:disabled="type !== 'transfer'"
                                :error="$errors->first('to_account_id')">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" x-bind:disabled="accountId === '{{ $account->id }}'">{{ $account->name }}</option>
                                @endforeach
                            </x-ui.forms.select>
                            <p class="mt-1.5 text-xs text-slate-400" x-show="to">
                                Saldo actual: <span class="font-semibold text-slate-700 tabular-nums" x-text="to && fmt(to.balance)"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </x-ui.infolist.section>

            {{-- Fila 2: monto y vista previa --}}
            <x-ui.infolist.section title="Monto" icon="heroicon-o-banknotes" :cols="0"
                description="Revisa en cuánto queda cada cuenta antes de guardar.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.input label="Monto" name="amount" type="number" step="0.01" min="0.01" required
                        :addonLeft="$currency" x-model="amount" :error="$errors->first('amount')"
                        hint="Escríbelo sin separadores de miles." />
                    <x-ui.forms.input label="Fecha" name="movement_date" type="date" required max="{{ now()->toDateString() }}"
                        :value="old('movement_date', now()->toDateString())" :error="$errors->first('movement_date')"
                        hint="El día en que pasó el dinero." />

                    <div class="sm:col-span-2">
                        <x-ui.forms.input label="Nota" name="notes" maxlength="255" :value="old('notes')"
                            placeholder="Ej: Depósito del efectivo de la semana" :error="$errors->first('notes')"
                            hint="Queda en el movimiento: explica de dónde viene o a dónde va el dinero." />
                    </div>
                </div>

                {{-- Vista previa: saldo actual → saldo después --}}
                <div class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm" x-show="from && value > 0" x-cloak>
                    <p class="flex flex-wrap items-center gap-x-2">
                        <span class="font-medium text-gray-700" x-text="from && from.name"></span>
                        <span class="tabular-nums text-gray-500" x-text="from && fmt(from.balance)"></span>
                        <span class="text-gray-400">→</span>
                        <span class="font-bold tabular-nums" :class="invalid ? 'text-state-error' : 'text-gray-900'" x-text="fromAfter !== null && fmt(fromAfter)"></span>
                    </p>
                    <p class="flex flex-wrap items-center gap-x-2 mt-1" x-show="to">
                        <span class="font-medium text-gray-700" x-text="to && to.name"></span>
                        <span class="tabular-nums text-gray-500" x-text="to && fmt(to.balance)"></span>
                        <span class="text-gray-400">→</span>
                        <span class="font-bold tabular-nums text-gray-900" x-text="toAfter !== null && fmt(toAfter)"></span>
                    </p>
                    <p class="mt-2 text-xs text-state-error" x-show="invalid">
                        Saldo insuficiente. Si la cuenta tiene más dinero del que dice el sistema, concíliala primero.
                    </p>
                </div>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('finance.treasury.movements.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check" disabledWhen="invalid">
                    Registrar movimiento
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
