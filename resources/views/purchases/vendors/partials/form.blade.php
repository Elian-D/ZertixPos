{{-- Formulario de proveedor (v1.5.0 REQ-4.1, /filament-form) en tres pestañas: Datos
     generales (con el lookup de la DGII que autollena la razón social), Contacto y Términos
     financieros. Lo usan create.blade.php y edit.blade.php sin cambios; con errores abre en
     la primera pestaña que tenga uno.
     Recibe $taxTypes, $conditions, $accounts (activas); opcional $vendor (editar). --}}
@php
    $tabErrors = [
        'general' => $errors->hasAny(['business_name', 'trade_name', 'tax_identifier_type', 'tax_id', 'is_active']),
        'contact' => $errors->hasAny(['contact_name', 'phone', 'email', 'address']),
        'terms' => $errors->hasAny(['payment_condition', 'credit_days', 'default_financial_account_id', 'notes']),
    ];
    $tabs = [
        ['name' => 'general', 'label' => 'Datos generales', 'icon' => 'heroicon-o-identification', 'error' => $tabErrors['general']],
        ['name' => 'contact', 'label' => 'Contacto', 'icon' => 'heroicon-o-phone', 'error' => $tabErrors['contact']],
        ['name' => 'terms', 'label' => 'Términos financieros', 'icon' => 'heroicon-o-banknotes', 'error' => $tabErrors['terms']],
    ];
    $defaultTab = array_key_first(array_filter($tabErrors)) ?? 'general';
@endphp

<div x-data="{
        taxType: @js(old('tax_identifier_type', $vendor->tax_identifier_type ?? 'RNC')),
        taxId: @js((string) old('tax_id', $vendor->tax_id ?? '')),
        businessName: @js((string) old('business_name', $vendor->business_name ?? '')),
        tradeName: @js((string) old('trade_name', $vendor->trade_name ?? '')),
        condition: @js(old('payment_condition', $vendor->payment_condition ?? 'cash')),
        lookup: { loading: false, error: '', found: '' },
        async findRnc() {
            const rnc = this.taxId.replace(/\D/g, '');
            if (rnc.length !== 9 && rnc.length !== 11) {
                this.lookup = { loading: false, error: 'El RNC tiene 9 dígitos y la cédula 11.', found: '' };
                return;
            }
            this.lookup = { loading: true, error: '', found: '' };
            try {
                const response = await fetch(@js(route('purchases.rnc-lookup')) + '?rnc=' + rnc, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok || data.error) {
                    this.lookup = { loading: false, error: data.mensaje || data.message || 'No se encontró en la DGII.', found: '' };
                    return;
                }
                const name = data.nombre_razon_social || '';
                this.businessName = name || this.businessName;
                if (!this.tradeName && data.nombre_comercial) this.tradeName = data.nombre_comercial;
                this.taxType = rnc.length === 11 ? 'CEDULA' : 'RNC';
                this.lookup = { loading: false, error: '', found: name };
            } catch (e) {
                this.lookup = { loading: false, error: 'Error de conexión al consultar la DGII.', found: '' };
            }
        },
    }">

<x-ui.infolist.tabs :tabs="$tabs" :default="$defaultTab">

    {{-- PESTAÑA 1: Datos generales --}}
    <x-ui.infolist.tab name="general" class="!space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-[12rem_1fr] gap-x-6 gap-y-5">
            <x-ui.forms.select label="Tipo de documento" name="tax_identifier_type" x-model="taxType" placeholder="Sin documento"
                :error="$errors->first('tax_identifier_type')">
                @foreach($taxTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.forms.select>

            <div>
                <span class="block text-xs font-semibold text-slate-600 mb-1.5">RNC o cédula</span>
                <div class="flex gap-2 items-start">
                    <div class="flex-1 min-w-0">
                        <x-ui.forms.input name="tax_id" x-model="taxId" inputmode="numeric" maxlength="20" placeholder="Ej. 131987452"
                            x-on:keydown.enter.prevent="findRnc()" :error="$errors->first('tax_id')" />
                    </div>
                    <x-ui.button type="button" variant="secondary" appearance="outline" iconLeft="heroicon-s-magnifying-glass"
                        x-on:click="findRnc()" x-bind:disabled="lookup.loading">
                        <span x-text="lookup.loading ? 'Buscando…' : 'Buscar en DGII'"></span>
                    </x-ui.button>
                </div>
                <p class="mt-1.5 text-xs text-state-error" x-show="lookup.error" x-text="lookup.error" x-cloak></p>
                <p class="mt-1.5 text-xs text-state-success" x-show="lookup.found" x-cloak>
                    Encontrado en la DGII: <span class="font-semibold" x-text="lookup.found"></span>
                </p>
                @unless($errors->has('tax_id'))
                    <p class="mt-1.5 text-xs text-slate-400" x-show="!lookup.error && !lookup.found">Busca el RNC para llenar la razón social desde la DGII.</p>
                @endunless
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <x-ui.forms.input label="Razón social" name="business_name" x-model="businessName" required maxlength="150"
                placeholder="Ej. Distribuidora Oriental SRL" hint="El nombre legal, como sale en la factura del proveedor."
                :error="$errors->first('business_name')" />
            <x-ui.forms.input label="Nombre comercial" name="trade_name" x-model="tradeName" maxlength="150"
                placeholder="Ej. La Oriental" hint="Opcional. Es el que se muestra en las listas."
                :error="$errors->first('trade_name')" />
        </div>

        <x-ui.forms.toggle label="Proveedor activo" name="is_active" value="1"
            :checked="(bool) old('is_active', $vendor->is_active ?? true)"
            description="Un proveedor inactivo no aparece al registrar gastos ni compras." />
    </x-ui.infolist.tab>

    {{-- PESTAÑA 2: Contacto --}}
    <x-ui.infolist.tab name="contact" class="!space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <x-ui.forms.input label="Persona de contacto" name="contact_name" maxlength="100" iconLeft="heroicon-s-user"
                :value="old('contact_name', $vendor->contact_name ?? '')" placeholder="Ej. Juan Pérez (vendedor)"
                :error="$errors->first('contact_name')" />
            <x-ui.forms.input label="Teléfono" name="phone" type="tel" maxlength="30" iconLeft="heroicon-s-phone"
                :value="old('phone', $vendor->phone ?? '')" placeholder="809-000-0000" :error="$errors->first('phone')" />
            <x-ui.forms.input label="Correo" name="email" type="email" maxlength="150" iconLeft="heroicon-s-envelope"
                :value="old('email', $vendor->email ?? '')" placeholder="ventas@proveedor.com" :error="$errors->first('email')" />
            <x-ui.forms.input label="Dirección" name="address" maxlength="255" iconLeft="heroicon-s-map-pin"
                :value="old('address', $vendor->address ?? '')" :error="$errors->first('address')" />
        </div>
    </x-ui.infolist.tab>

    {{-- PESTAÑA 3: Términos financieros --}}
    <x-ui.infolist.tab name="terms" class="!space-y-6">
        <div>
            <span class="block text-xs font-semibold text-slate-600 mb-1.5">Condición de pago por defecto <span class="text-state-error">*</span></span>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach($conditions as $value => $label)
                    <x-ui.forms.radio :label="$label" name="payment_condition" :value="$value" x-model="condition" />
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-slate-400">Se propone al registrar un gasto o una compra con este proveedor; se puede cambiar en cada uno.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <div x-show="condition === 'credit'" x-cloak>
                <x-ui.forms.input label="Días de crédito" name="credit_days" type="number" min="1" max="365"
                    :value="old('credit_days', ($vendor->credit_days ?? 0) ?: 30)" x-bind:disabled="condition !== 'credit'"
                    hint="El vencimiento de lo que se le debe: fecha del gasto o la compra más estos días."
                    :error="$errors->first('credit_days')" />
            </div>

            <x-ui.forms.select label="Cuenta de pago sugerida" name="default_financial_account_id" placeholder="Sin cuenta sugerida"
                hint="De Cajas y Bancos. Viene elegida al pagarle; se puede cambiar."
                :error="$errors->first('default_financial_account_id')">
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) old('default_financial_account_id', $vendor->default_financial_account_id ?? '') === (string) $account->id)>
                        {{ $account->name }}
                    </option>
                @endforeach
            </x-ui.forms.select>
        </div>

        <x-ui.forms.textarea label="Notas" name="notes" :rows="3" maxlength="1000"
            placeholder="Ej. Entrega martes y viernes; pedido mínimo RD$5,000"
            :error="$errors->first('notes')">{{ old('notes', $vendor->notes ?? '') }}</x-ui.forms.textarea>
    </x-ui.infolist.tab>
</x-ui.infolist.tabs>
</div>
