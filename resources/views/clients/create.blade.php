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
            
            <x-ui.toasts />
            
            <x-form-header
            title="Nuevo Cliente"
            subtitle="Complete todos los campos requeridos para la gestión comercial."
            :back-route="route('clients.index')" />
            
            
            <div class="p-8 space-y-8">
                {{-- Bloque 1: Datos de Identidad --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">1</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Identidad Fiscal y Nombre</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                        {{-- 1. Nombre del cliente --}}
                        <div class="md:col-span-4">
                            <x-input-label value="Nombre Completo / Razón Social" />
                            <x-text-input name="name" class="w-full mt-1" :value="old('name', $client->name ?? '')" required />
                            </div>
                            
                            {{-- 2. Tipo de cliente --}}
                            @if(isset($client)) @method('PUT')          @endif
                            <div class="md:col-span-2">
                                <x-input-label value="Tipo de Cliente" />
                                <select name="type" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                <option value="individual" {{ (old('type', $client->type ?? '') == 'individual') ? 'selected' : '' }}>Persona Física</option>
                                <option value="company" {{ (old('type', $client->type ?? '') == 'company') ? 'selected' : '' }}>Empresa / Corporativo</option>
                            </select>
                        </div>

                        {{-- 3. Tipo de identificador --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Tipo de ID Fiscal" />
                            <select name="tax_identifier_type" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                @foreach($taxIdentifierTypes as $type)
                                    <option value="{{ $type['value'] }}" {{ (old('tax_identifier_type', $client->tax_identifier_type?->value ?? '') == $type['value']) ? 'selected' : '' }}>
                                        {{ $type['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 4. Número Identificador --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Número de ID Fiscal" />
                            <x-text-input name="tax_id" class="w-full mt-1" :value="old('tax_id', $client->tax_id ?? '')" />
                        </div>

                        {{-- 5. Estado --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Estado Operativo" />
                            <select name="estado_cliente_id" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                @foreach($estados as $e)
                                    <option value="{{ $e->id }}" {{ (old('estado_cliente_id', $client->estado_cliente_id ?? '') == $e->id) ? 'selected' : '' }}>
                                        {{ $e->nombre }}
                                    </option>
                                @endforeach
                            </select>
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
                            <x-input-label value="Correo Electrónico" />
                            <x-text-input name="email" type="email" class="w-full mt-1" :value="old('email', $client->email ?? '')" />
                        </div>
                        <div>
                            <x-input-label value="Teléfono de Contacto" />
                            <x-text-input name="phone" class="w-full mt-1" :value="old('phone', $client->phone ?? '')" />
                        </div>
                        <div>
                            <x-input-label value="Provincia" />
                            <select name="provincia_id" x-model="selectedProvincia" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                @foreach($states as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Municipio" />
                            <select name="municipio_id" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                <option value="">Sin especificar</option>
                                <template x-for="m in municipiosDeProvincia" :key="m.id">
                                    <option :value="m.id" x-text="m.name" :selected="m.id == {{ old('municipio_id', $client->municipio_id ?? 'null') }}"></option>
                                </template>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label value="Dirección Exacta" />
                            <textarea name="address" rows="2" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm p-2.5" placeholder="Calle, número, edificio...">{{ old('address', $client->address ?? '') }}</textarea>
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
                        {{-- Límite de Crédito --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Límite de Crédito ({{ config('regional.currency_symbol') }})" />
                            <x-text-input name="credit_limit" type="number" step="0.01" class="w-full mt-1 font-mono" 
                                :value="old('credit_limit', '0.00')" placeholder="0.00" />
                        </div>

                        {{-- Días de Crédito --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Días de Crédito (Vencimiento)" />
                            <x-text-input name="payment_terms" type="number" class="w-full mt-1" 
                                :value="old('payment_terms', '0')" placeholder="Ej: 30" />
                        </div>
                        @if (module_enabled('accounting.advanced'))
                            
                        {{-- Cuenta Contable --}}
                        <div class="md:col-span-4" x-data="{ createAccount: false }">
                            <x-input-label value="Cuenta Contable (CxC)" />
                            
                            <div class="mt-1 space-y-2"> {{-- Ajustado a mt-1 para alinear con los otros inputs --}}
                                {{-- Selector: Solo muestra la General en Create --}}
                                <select name="accounting_account_id" 
                                x-show="!createAccount"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                                <option value="">Usar Cuenta General (1.1.02)</option>
                                </select>

                                {{-- Toggle para creación automática --}}
                                <label class="flex items-center cursor-pointer gap-2 p-2 bg-indigo-50 rounded-lg border border-indigo-100">
                                    <input type="checkbox" name="create_accounting_account" value="1" 
                                        x-model="createAccount"
                                        class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                    <span class="text-[11px] font-bold text-indigo-700 uppercase tracking-tight">¿Crear cuenta específica?</span>
                                </label>
                                
                                <p x-show="createAccount" class="text-[10px] text-indigo-500 italic leading-tight">
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
                <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 shadow-lg px-8">
                    Registrar Cliente
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>