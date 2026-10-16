{{-- Conciliar una cuenta de Cajas y Bancos (v1.5.0 REQ-3.3) — /filament-form. No mueve
     dinero: se le dice al sistema cuánto hay de verdad (efectivo contado o estado del banco)
     y la diferencia queda ajustada con su motivo.
     Recibe: $accounts (activas, con lastReconciliation), $selected (?account=) y $reasons. --}}
@php
    $currency = config('regional.currency_symbol');
    $data = $accounts->mapWithKeys(fn ($a) => [$a->id => [
        'name' => $a->name,
        'bank' => $a->isBank(),
        'balance' => (float) $a->current_balance,
        'last' => $a->lastReconciliation?->reconciled_at?->format('d/m/Y'),
    ]]);
@endphp

<x-app-layout title="Conciliar cuenta">
    <div class="p-4 md:p-6"
        x-data="{
            accounts: @js($data),
            accountId: @js((string) old('financial_account_id', $selected ?? '')),
            real: @js((string) old('real_balance', '')),
            reason: @js((string) old('reason', '')),
            get account() { return this.accounts[this.accountId] || null },
            get diff() {
                const r = parseFloat(this.real);
                return (!this.account || isNaN(r)) ? null : Math.round((r - this.account.balance) * 100) / 100;
            },
            fmt(v) { return (v < 0 ? '−' : '') + @js($currency) + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
        }">

        <x-ui.page-header title="Conciliar cuenta"
            description="Escribe cuánto hay ahora de verdad: la cuenta queda en ese número y la diferencia se ajusta con su motivo. Para sumar dinero que entró, usa Depósito." />

        <form method="POST" action="{{ route('finance.treasury.reconcile') }}" class="mt-6 flex flex-col gap-6">
            @csrf

            {{-- Fila 1: la cuenta --}}
            <x-ui.infolist.section title="Cuenta" icon="heroicon-o-building-library" :cols="0"
                description="Qué cuenta vas a comparar con la realidad.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.select label="Cuenta" name="financial_account_id" x-model="accountId" placeholder="Seleccione la cuenta..." required
                        :error="$errors->first('financial_account_id')">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <dl class="grid grid-cols-2 gap-4 text-sm" x-show="account" x-cloak>
                        <div>
                            <dt class="text-xs font-semibold text-slate-600">Según el sistema</dt>
                            <dd class="mt-1.5 font-bold tabular-nums text-gray-900" x-text="account && fmt(account.balance)"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-600">Última conciliación</dt>
                            <dd class="mt-1.5 text-gray-700" x-text="account && (account.last || 'Nunca')"></dd>
                        </div>
                    </dl>
                </div>
            </x-ui.infolist.section>

            {{-- Fila 2: lo real --}}
            <x-ui.infolist.section title="Saldo real" icon="heroicon-o-scale" :cols="0"
                description="No se suma ni se resta: reemplaza lo que dice el sistema.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <x-ui.forms.input label="¿Cuánto hay ahora?" name="real_balance" type="number" step="0.01" min="0" required
                            :addonLeft="$currency" x-model="real" :error="$errors->first('real_balance')" />
                        {{-- Hint según el tipo de cuenta (el componente solo admite uno fijo) --}}
                        @unless($errors->has('real_balance'))
                            <p class="mt-1.5 text-xs text-slate-400">
                                <span x-show="!account || !account.bank">Cuenta el efectivo de la caja y escribe el total.</span>
                                <span x-show="account && account.bank" x-cloak>Escribe el saldo del estado de cuenta o de la app del banco.</span>
                            </p>
                        @endunless
                    </div>
                    <x-ui.forms.input label="Fecha" name="reconciled_at" type="date" required max="{{ now()->toDateString() }}"
                        :value="old('reconciled_at', now()->toDateString())" :error="$errors->first('reconciled_at')"
                        hint="El día en que contaste o el del estado del banco." />
                </div>

                {{-- Diferencia y saldo final, en vivo --}}
                <dl class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm grid grid-cols-2 gap-y-1 sm:max-w-md" x-show="diff !== null" x-cloak>
                    <dt class="text-gray-500">Según el sistema</dt>
                    <dd class="text-right tabular-nums" x-text="account && fmt(account.balance)"></dd>
                    <dt class="text-gray-500">Diferencia</dt>
                    <dd class="text-right font-semibold tabular-nums">
                        <span x-show="diff === 0" class="text-state-success">Cuadra</span>
                        <span x-show="diff > 0" class="text-state-success" x-text="'+' + fmt(diff)"></span>
                        <span x-show="diff < 0" class="text-state-error" x-text="fmt(diff)"></span>
                    </dd>
                    <dt class="text-gray-500">La cuenta queda en</dt>
                    <dd class="text-right font-bold tabular-nums text-gray-900" x-text="fmt(parseFloat(real))"></dd>
                </dl>
            </x-ui.infolist.section>

            {{-- Fila 3: por qué no cuadra --}}
            <x-ui.infolist.section title="Motivo" icon="heroicon-o-chat-bubble-left-ellipsis" :cols="0"
                description="Solo si hay diferencia: queda en el historial para ver qué se repite.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.select label="Motivo de la diferencia" name="reason" placeholder="Seleccione el motivo..." x-model="reason"
                        x-bind:required="diff !== null && diff !== 0" x-bind:disabled="diff === null || diff === 0"
                        hint="Las comisiones del banco por las ventas con tarjeta van como «Comisión o cargo bancario»."
                        :error="$errors->first('reason')">
                        @foreach($reasons as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <x-ui.forms.input label="Nota" name="notes" maxlength="255" :value="old('notes')"
                        x-bind:required="reason === 'other' && diff !== 0"
                        placeholder="Ej: Comisiones de octubre" hint="Obligatoria si el motivo es «Otro»."
                        :error="$errors->first('notes')" />
                </div>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('finance.treasury.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-scale">
                    Conciliar
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
