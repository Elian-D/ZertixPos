<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
            <form action="{{ route('clients.store') }}" method="POST"
                class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100"
                x-data="{
                    municipalities: {{ $municipalities->toJson() }},
                    selectedProvincia: '{{ old('provincia_id', $client->provincia_id ?? '') }}',
                    get municipiosDeProvincia() {
                        return this.municipalities.filter(m => m.province_id == this.selectedProvincia);
                    }
                }">
            @csrf
            
            
            <x-form-header
            title="Nuevo Cliente"
            subtitle="Complete todos los campos requeridos para la gestión comercial."
            :back-route="route('clients.index')" />
            
            
            <div class="p-8 space-y-8">
                {{-- Bloque 1: Datos de Identidad --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-zertix-primary-50 text-zertix-primary-600 rounded-full flex items-center justify-center font-bold text-sm">1</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Identidad Fiscal y Nombre</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                        {{-- 1. Nombre del cliente --}}
                        <div class="md:col-span-4">
                            <x-ui.forms.input
                                label="Nombre Completo / Razón Social"
                                name="name"
                                value="{{ old('name', $client->name ?? '') }}"
                                :error="$errors->first('name')"
                                required
                            />
                        </div>

                        {{-- 2. Tipo de cliente --}}
                        @if(isset($client)) @method('PUT')          @endif
                        <div class="md:col-span-2">
                            <x-ui.forms.select
                                label="Tipo de Cliente"
                                name="type"
                                :error="$errors->first('type')"
                                required
                            >
                                <option value="individual" {{ (old('type', $client->type ?? '') == 'individual') ? 'selected' : '' }}>Persona Física</option>
                                <option value="company" {{ (old('type', $client->type ?? '') == 'company') ? 'selected' : '' }}>Empresa / Corporativo</option>
                            </x-ui.forms.select>
                        </div>

                        {{-- 3. Tipo de identificador --}}
                        <div class="md:col-span-2">
                            <x-ui.forms.select
                                label="Tipo de ID Fiscal"
                                name="tax_identifier_type"
                                :error="$errors->first('tax_identifier_type')"
                                required
                            >
                                @foreach($taxIdentifierTypes as $type)
                                    <option value="{{ $type['value'] }}" {{ (old('tax_identifier_type', $client->tax_identifier_type?->value ?? '') == $type['value']) ? 'selected' : '' }}>
                                        {{ $type['label'] }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>

                        {{-- 4. Número Identificador --}}
                        <div class="md:col-span-2">
                            <x-ui.forms.input
                                label="Número de ID Fiscal"
                                name="tax_id"
                                value="{{ old('tax_id', $client->tax_id ?? '') }}"
                                hint="Cédula, RNC o Pasaporte según el tipo seleccionado"
                                :error="$errors->first('tax_id')"
                                required
                            />
                        </div>

                        {{-- 5. Estado --}}
                        <div class="md:col-span-2 flex items-center bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <input type="hidden" name="is_active" value="0">
                            <x-ui.forms.checkbox
                                label="Cliente Activo"
                                name="is_active"
                                value="1"
                                :checked="old('is_active', true)"
                                description="Un cliente inactivo no aparece en el POS ni puede facturarse."
                            />
                        </div>
                    </div>
                </section>

                {{-- Bloque 2: Contacto y Localización --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center font-bold text-sm">2</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Contacto y Ubicación</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-ui.forms.input
                                label="Correo Electrónico"
                                name="email"
                                type="email"
                                value="{{ old('email', $client->email ?? '') }}"
                                :error="$errors->first('email')"
                            />
                        </div>
                        <div>
                            <x-ui.forms.input
                                label="Teléfono de Contacto"
                                name="phone"
                                value="{{ old('phone', $client->phone ?? '') }}"
                                :error="$errors->first('phone')"
                            />
                        </div>
                        <div>
                            <x-ui.forms.select
                                label="Provincia"
                                name="provincia_id"
                                x-model="selectedProvincia"
                                :error="$errors->first('provincia_id')"
                                required
                            >
                                @foreach($states as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                        <div>
                            <x-ui.forms.select
                                label="Municipio"
                                name="municipio_id"
                                placeholder=""
                                :error="$errors->first('municipio_id')"
                            >
                                <option value="">Sin especificar</option>
                                <template x-for="m in municipiosDeProvincia" :key="m.id">
                                    <option :value="m.id" x-text="m.name" :selected="m.id == {{ old('municipio_id', $client->municipio_id ?? 'null') }}"></option>
                                </template>
                            </x-ui.forms.select>
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.forms.textarea
                                label="Dirección Exacta"
                                name="address"
                                :rows="2"
                                placeholder="Calle, número, edificio..."
                                :error="$errors->first('address')"
                            >{{ old('address', $client->address ?? '') }}</x-ui.forms.textarea>
                        </div>
                    </div>
                </section>

                {{-- Bloque 3: Configuración Financiera y Contable --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center font-bold text-sm">3</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Configuración Contable y Crédito</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start"> {{-- items-start asegura alineación superior --}}
                        {{-- Límite de Crédito y Días de Crédito solo son editables con
                             sales.receivables activo (núcleo flexible, REQ-10.5) — se
                             deshabilitan, no se ocultan: pueden traer datos de cuando el
                             módulo estaba activo, y ocultarlos da la sensación de que se
                             perdieron. --}}
                        @php $creditDisabled = ! module_enabled('sales.receivables'); @endphp
                        {{-- Límite de Crédito --}}
                        <div class="md:col-span-2">
                            {{-- readonly, no disabled: ambos campos son 'required' en
                                 StoreClientRequest — un input disabled no se envía en el
                                 POST y el formulario fallaría con "campo requerido". --}}
                            <x-ui.forms.input
                                label="Límite de Crédito ({{ config('regional.currency_symbol') }})"
                                name="credit_limit"
                                type="number"
                                step="0.01"
                                class="font-mono {{ $creditDisabled ? 'bg-gray-50 cursor-not-allowed text-gray-400' : '' }}"
                                value="{{ old('credit_limit', '0.00') }}"
                                placeholder="0.00"
                                :readonly="$creditDisabled"
                                :error="$errors->first('credit_limit')"
                                required
                            />
                        </div>

                        {{-- Días de Crédito --}}
                        <div class="md:col-span-2">
                            <x-ui.forms.input
                                label="Días de Crédito (Vencimiento)"
                                name="payment_terms"
                                type="number"
                                class="{{ $creditDisabled ? 'bg-gray-50 cursor-not-allowed text-gray-400' : '' }}"
                                value="{{ old('payment_terms', '0') }}"
                                placeholder="Ej: 30"
                                :readonly="$creditDisabled"
                                :error="$errors->first('payment_terms')"
                                required
                            />
                        </div>
                        @unless (module_enabled('sales.receivables'))
                            <p class="md:col-span-4 -mt-2 text-xs text-amber-600 italic">
                                Se activa cuando el módulo de Cuentas por Cobrar está activo.
                            </p>
                        @endunless
                        @if (module_enabled('accounting.advanced'))
                            
                        {{-- Cuenta Contable --}}
                        <div class="md:col-span-4" x-data="{ createAccount: false }">
                            <div class="space-y-2">
                                {{-- Selector: Solo muestra la General en Create --}}
                                <x-ui.forms.select
                                    label="Cuenta Contable (CxC)"
                                    name="accounting_account_id"
                                    x-show="!createAccount"
                                    placeholder=""
                                    :error="$errors->first('accounting_account_id')"
                                >
                                    <option value="">Usar Cuenta General (1.1.02)</option>
                                </x-ui.forms.select>

                                {{-- Toggle para creación automática --}}
                                <div class="p-2 bg-zertix-primary-50 rounded-lg border border-zertix-primary-100">
                                    <x-ui.forms.checkbox
                                        label="¿Crear cuenta específica?"
                                        name="create_accounting_account"
                                        value="1"
                                        x-model="createAccount"
                                    />
                                </div>

                                <p x-show="createAccount" class="text-[10px] text-zertix-primary-500 italic leading-tight">
                                    * Se creará automáticamente la cuenta "CxC - [Nombre]"
                                </p>
                            </div>
                        </div>
                        @endif            
                    </div>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <a href="{{ route('clients.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancelar</a>
                <x-ui.button type="submit" variant="primary" class="shadow-lg px-8">
                    Registrar Cliente
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>