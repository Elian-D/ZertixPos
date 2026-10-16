{{-- Registrar gasto (v1.5.0 REQ-4.4) — /filament-form, una columna:
     1. Qué se gastó (categoría, concepto, proveedor opcional, factura, fecha y monto).
     2. Cómo se paga: de contado sale ya de una cuenta (vista previa del saldo, nunca en
        negativo); a crédito nace una cuenta por pagar con su vencimiento.
     Al elegir un proveedor se proponen su condición, su cuenta sugerida y sus días de crédito.
     Recibe: $categories, $categoryHints, $conditions, $accounts (activas, con saldo), $vendors
     (vacío sin el módulo de compras) y $selectedVendor (?vendor=). --}}
@php
    $currency = config('regional.currency_symbol');
    $accountData = $accounts->mapWithKeys(fn ($a) => [$a->id => ['name' => $a->name, 'balance' => (float) $a->current_balance]]);
    $vendorData = $vendors->mapWithKeys(fn ($v) => [$v->id => [
        'condition' => $v->payment_condition,
        'days' => (int) $v->credit_days,
        'account' => $v->default_financial_account_id,
    ]]);
    $today = now()->toDateString();
@endphp

<x-app-layout title="Registrar gasto">
    <div class="p-4 md:p-6"
        x-data="{
            accounts: @js($accountData),
            vendors: @js($vendorData),
            hints: @js($categoryHints),
            category: @js((string) old('category', '')),
            vendorId: @js((string) old('vendor_id', $selectedVendor ?? '')),
            condition: @js(old('payment_condition', 'cash')),
            accountId: @js((string) old('financial_account_id', '')),
            amount: @js((string) old('amount', '')),
            date: @js(old('expense_date', $today)),
            dueDate: @js((string) old('due_date', '')),
            dueTouched: @js((bool) old('due_date')),
            init() {
                if (this.vendorId && !@js((bool) old('payment_condition'))) this.applyVendor();
                if (!this.dueDate) this.syncDue();
                this.$watch('vendorId', () => this.applyVendor());
                this.$watch('date', () => this.syncDue());
            },
            // El proveedor propone su condición, su cuenta sugerida y su vencimiento.
            applyVendor() {
                const v = this.vendors[this.vendorId];
                if (!v) return;
                this.condition = v.condition;
                if (v.account && this.accounts[v.account]) this.accountId = String(v.account);
                this.dueTouched = false;
                this.syncDue();
            },
            syncDue() {
                if (this.dueTouched || !this.date) return;
                const days = (this.vendors[this.vendorId] || {}).days || 0;
                const d = new Date(this.date + 'T12:00:00');
                d.setDate(d.getDate() + days);
                this.dueDate = d.toISOString().slice(0, 10);
            },
            get value() { const a = parseFloat(this.amount); return isNaN(a) ? 0 : a },
            get account() { return this.accounts[this.accountId] || null },
            get after() { return this.account ? Math.round((this.account.balance - this.value) * 100) / 100 : null },
            get invalid() { return this.condition === 'cash' && this.after !== null && this.after < 0 },
            fmt(v) { return (v < 0 ? '−' : '') + @js($currency) + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
        }">

        <x-ui.page-header title="Registrar gasto"
            description="Lo que consume el negocio: luz, alquiler, gasolina… De contado sale ya de una cuenta; a crédito queda como cuenta por pagar." />

        <form method="POST" action="{{ route('finance.expenses.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf

            {{-- Fila 1: qué se gastó --}}
            <x-ui.infolist.section title="Gasto" icon="heroicon-o-receipt-percent" :cols="0" description="Qué se consumió y cuánto costó.">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <x-ui.forms.select label="Categoría" name="category" x-model="category" placeholder="Seleccione la categoría..." required
                            :error="$errors->first('category')">
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.forms.select>
                        @unless($errors->has('category'))
                            <p class="mt-1.5 text-xs text-slate-400" x-text="hints[category] || 'Sirve para ver en qué se va el dinero del negocio.'"></p>
                        @endunless
                    </div>

                    <x-ui.forms.input label="Concepto" name="concept" required maxlength="150" :value="old('concept')"
                        placeholder="Ej. Factura de luz de octubre" :error="$errors->first('concept')" />

                    @if($vendors->isNotEmpty())
                        <x-ui.forms.select label="Proveedor" name="vendor_id" x-model="vendorId" placeholder="Sin proveedor"
                            hint="Opcional. Propone su condición de pago, su cuenta y sus días de crédito."
                            :error="$errors->first('vendor_id')">
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->display_name }}</option>
                            @endforeach
                        </x-ui.forms.select>
                    @endif

                    <x-ui.forms.input label="Factura o NCF del proveedor" name="supplier_invoice" maxlength="50" :value="old('supplier_invoice')"
                        placeholder="Ej. B0100000123" hint="Opcional. Para ubicar el comprobante después."
                        :error="$errors->first('supplier_invoice')" />

                    <x-ui.forms.input label="Fecha" name="expense_date" type="date" required max="{{ $today }}" x-model="date"
                        hint="El día del gasto." :error="$errors->first('expense_date')" />

                    <x-ui.forms.input label="Monto" name="amount" type="number" step="0.01" min="0.01" required
                        :addonLeft="$currency" x-model="amount" hint="Lo que se pagó o se debe, con impuestos incluidos."
                        :error="$errors->first('amount')" />
                </div>
            </x-ui.infolist.section>

            {{-- Fila 2: cómo se paga --}}
            <x-ui.infolist.section title="Pago" icon="heroicon-o-banknotes" :cols="0" description="De contado sale ya de una cuenta; a crédito se paga después.">
                <div class="flex flex-col gap-5">
                    <div>
                        <span class="block text-xs font-semibold text-slate-600 mb-1.5">Condición <span class="text-state-error">*</span></span>
                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            @foreach($conditions as $value => $label)
                                <x-ui.forms.radio :label="$label" name="payment_condition" :value="$value" x-model="condition" />
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">
                            <span x-show="condition === 'cash'">El dinero sale ahora de la cuenta que elijas.</span>
                            <span x-show="condition === 'credit'" x-cloak>Nace una cuenta por pagar; se paga desde Cuentas por Pagar.</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                        <div x-show="condition === 'cash'">
                            <x-ui.forms.select label="Cuenta" name="financial_account_id" x-model="accountId" placeholder="Seleccione la cuenta..."
                                x-bind:required="condition === 'cash'" x-bind:disabled="condition !== 'cash'"
                                :error="$errors->first('financial_account_id')">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </x-ui.forms.select>
                            <p class="mt-1.5 text-xs text-slate-400" x-show="account">
                                Saldo actual: <span class="font-semibold text-slate-700 tabular-nums" x-text="account && fmt(account.balance)"></span>
                            </p>
                        </div>

                        <div x-show="condition === 'credit'" x-cloak>
                            <x-ui.forms.input label="Vencimiento" name="due_date" type="date" x-model="dueDate"
                                x-bind:required="condition === 'credit'" x-bind:disabled="condition !== 'credit'"
                                x-on:input="dueTouched = true"
                                hint="Fecha del gasto más los días de crédito del proveedor; se puede cambiar."
                                :error="$errors->first('due_date')" />
                        </div>
                    </div>

                    {{-- Vista previa: cómo queda la cuenta --}}
                    <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm" x-show="condition === 'cash' && account && value > 0" x-cloak>
                        <p class="flex flex-wrap items-center gap-x-2">
                            <span class="font-medium text-gray-700" x-text="account && account.name"></span>
                            <span class="tabular-nums text-gray-500" x-text="account && fmt(account.balance)"></span>
                            <span class="text-gray-400">→</span>
                            <span class="font-bold tabular-nums" :class="invalid ? 'text-state-error' : 'text-gray-900'" x-text="after !== null && fmt(after)"></span>
                        </p>
                        <p class="mt-2 text-xs text-state-error" x-show="invalid">
                            Saldo insuficiente. Si el dinero está ahí, concilia la cuenta primero.
                        </p>
                    </div>

                    <x-ui.forms.textarea label="Nota" name="notes" :rows="2" maxlength="500"
                        :error="$errors->first('notes')">{{ old('notes') }}</x-ui.forms.textarea>
                </div>
            </x-ui.infolist.section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('finance.expenses.index') }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check" disabledWhen="invalid">Registrar gasto</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
