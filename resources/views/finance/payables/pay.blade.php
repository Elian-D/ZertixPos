{{-- Registrar pago de una cuenta por pagar (v1.5.0 REQ-4.3) — /filament-form con la vista
     previa de cómo quedan la cuenta y lo pendiente. Parcial permitido; nunca más de lo
     pendiente ni dejando la cuenta en negativo (el servicio lo valida igual).
     Recibe $payable (con vendor), $accounts (activas, con saldo) y $suggested (cuenta del proveedor). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $accountData = $accounts->mapWithKeys(fn ($a) => [$a->id => ['name' => $a->name, 'balance' => (float) $a->current_balance]]);
    $default = $accounts->firstWhere('id', $suggested) ? (string) $suggested : '';
@endphp

<x-app-layout :title="'Pagar '.$payable->number">
    <div class="p-4 md:p-6"
        x-data="{
            accounts: @js($accountData),
            accountId: @js((string) old('financial_account_id', $default)),
            amount: @js((string) old('amount', (float) $payable->balance)),
            pending: {{ (float) $payable->balance }},
            get value() { const a = parseFloat(this.amount); return isNaN(a) ? 0 : a },
            get account() { return this.accounts[this.accountId] || null },
            get after() { return this.account ? Math.round((this.account.balance - this.value) * 100) / 100 : null },
            get left() { return Math.round((this.pending - this.value) * 100) / 100 },
            get tooMuch() { return this.value > this.pending },
            get noFunds() { return this.after !== null && this.after < 0 },
            fmt(v) { return (v < 0 ? '−' : '') + @js($currency) + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
        }">

        <x-ui.page-header :title="'Pagar '.$payable->number"
            :description="($payable->vendor?->display_name ? $payable->vendor->display_name.' · ' : '').$payable->concept" />

        <form method="POST" action="{{ route('finance.payables.pay', $payable) }}" class="mt-6 flex flex-col gap-6">
            @csrf

            {{-- Lo que se debe --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.stat label="Total" :value="$money($payable->total_amount)" :hint="'Vence el '.$payable->due_date->format('d/m/Y')" />
                <x-ui.stat label="Pagado" :value="$money($payable->paid_amount)" />
                <x-ui.stat label="Pendiente" :value="$money($payable->balance)" :variant="$payable->isOverdue() ? 'error' : 'default'"
                    :hint="$payable->isOverdue() ? 'Vencida' : null" />
            </div>

            <x-ui.infolist.section title="Pago" icon="heroicon-o-banknotes" :cols="0" description="De qué cuenta sale y cuánto. Puede ser un abono parcial.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <x-ui.forms.select label="Cuenta" name="financial_account_id" x-model="accountId" placeholder="Seleccione la cuenta..." required
                            :hint="$default ? 'La cuenta sugerida del proveedor.' : null" :error="$errors->first('financial_account_id')">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </x-ui.forms.select>
                        <p class="mt-1.5 text-xs text-slate-400" x-show="account">
                            Saldo actual: <span class="font-semibold text-slate-700 tabular-nums" x-text="account && fmt(account.balance)"></span>
                        </p>
                    </div>

                    <x-ui.forms.input label="Monto" name="amount" type="number" step="0.01" min="0.01" :max="(float) $payable->balance" required
                        :addonLeft="$currency" x-model="amount" hint="Viene con todo lo pendiente; bájalo para un abono."
                        :error="$errors->first('amount')" />

                    <x-ui.forms.input label="Fecha" name="payment_date" type="date" required max="{{ now()->toDateString() }}"
                        :value="old('payment_date', now()->toDateString())" :error="$errors->first('payment_date')" />

                    <x-ui.forms.input label="Referencia" name="reference" maxlength="100" :value="old('reference')"
                        placeholder="Ej. Transferencia 004512 o cheque 120" hint="Opcional. Para ubicarlo en el estado del banco."
                        :error="$errors->first('reference')" />

                    <div class="sm:col-span-2">
                        <x-ui.forms.textarea label="Nota" name="notes" :rows="2" maxlength="500"
                            :error="$errors->first('notes')">{{ old('notes') }}</x-ui.forms.textarea>
                    </div>
                </div>

                {{-- Vista previa --}}
                <div class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm space-y-1" x-show="value > 0" x-cloak>
                    <p class="flex flex-wrap items-center gap-x-2" x-show="account">
                        <span class="font-medium text-gray-700" x-text="account && account.name"></span>
                        <span class="tabular-nums text-gray-500" x-text="account && fmt(account.balance)"></span>
                        <span class="text-gray-400">→</span>
                        <span class="font-bold tabular-nums" :class="noFunds ? 'text-state-error' : 'text-gray-900'" x-text="after !== null && fmt(after)"></span>
                    </p>
                    <p class="flex flex-wrap items-center gap-x-2">
                        <span class="font-medium text-gray-700">Pendiente de {{ $payable->number }}</span>
                        <span class="tabular-nums text-gray-500" x-text="fmt(pending)"></span>
                        <span class="text-gray-400">→</span>
                        <span class="font-bold tabular-nums" :class="tooMuch ? 'text-state-error' : 'text-gray-900'" x-text="fmt(left)"></span>
                    </p>
                    <p class="text-xs text-state-error" x-show="tooMuch">El pago no puede superar lo pendiente.</p>
                    <p class="text-xs text-state-error" x-show="noFunds">Saldo insuficiente. Si el dinero está ahí, concilia la cuenta primero.</p>
                </div>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('finance.payables.show', $payable) }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check" disabledWhen="tooMuch || noFunds">Registrar pago</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
