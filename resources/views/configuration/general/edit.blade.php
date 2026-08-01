<x-config-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4"
        x-cloak
        x-data="{ 
            countries: {{ $countries->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'emoji' => $c->emoji, 'phonecode' => $c->phonecode])->toJson() }},
            states: {{ $states->toJson() }},
            usaNcf: {{ old('usa_ncf', $config->usa_ncf ?? false) ? 'true' : 'false' }},
            taxTypes: {{ $taxTypes->toJson() }},
            
            searchCountry: '',
            searchState: '',
            openCountry: false,
            openState: false,

            selectedCountry: '{{ old('country_id', $config->country_id ?? 62) }}',
            selectedState: '{{ old('state_id', $config->state_id ?? '') }}',
            selectedTaxType: '{{ old('tax_identifier_type_id', $config->tax_identifier_type_id ?? '') }}',
            selectedImpuesto: '{{ old('impuesto_id', $config->impuesto_id ?? '') }}',
            
            logoPreview: '{{ $config?->logo ? asset('storage/'.$config->logo) : '' }}',
            phoneCode: '{{ $countries->where('id', old('country_id', $config->country_id ?? 62))->first()->phonecode ?? '' }}',
            currency: '{{ old('currency', $config->currency ?? '---') }}',
            timezone: '{{ old('timezone', $config->timezone ?? 'UTC') }}',
            isLoading: false,

            updateLogoPreview(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => { this.logoPreview = e.target.result; };
                    reader.readAsDataURL(file);
                }
            },

            formatSearch(text) {
                if (!text) return '';
                return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            },

            get filteredCountries() {
                let s = this.formatSearch(this.searchCountry);
                return this.countries.filter(c => this.formatSearch(c.name).includes(s));
            },

            get filteredStates() {
                let s = this.formatSearch(this.searchState);
                return this.states.filter(st => this.formatSearch(st.name).includes(s));
            },

            async updateRegionalData(id) {
                this.selectedCountry = id;
                this.openCountry = false;
                this.searchCountry = '';
                this.isLoading = true;
                
                try {
                    const response = await fetch(`/api/countries/${id}`);
                    const data = await response.json();
                    this.states = data.states;
                    this.taxTypes = data.tax_types;
                    this.currency = data.currency;
                    this.timezone = data.timezone;
                    this.phoneCode = data.phonecode;
                    this.selectedState = ''; 
                    this.selectedTaxType = '';
                    this.searchState = '';
                } catch (error) { console.error(error); } 
                finally { this.isLoading = false; }
            }
        }">

        <div class="max-w-4xl mx-auto">
            <x-ui.toasts />

            <form method="POST" action="{{ route('configuration.general.update') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-visible relative">
                    <div x-show="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-10 rounded-3xl flex items-center justify-center">
                        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow-sm border border-slate-100">
                            <div class="w-4 h-4 border-2 border-indigo-600/20 border-t-indigo-600 rounded-full animate-spin"></div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Cargando datos...</span>
                        </div>
                    </div>

                    <div class="p-6 border-b border-slate-100 flex items-center gap-4">
                        <span class="flex-none w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold shadow-lg shadow-indigo-100">1</span>
                        <div>
                            <h2 class="font-bold text-slate-800 text-xl tracking-tight">Ubicación de Operación</h2>
                            <p class="text-sm text-slate-500">Región base para impuestos y formatos.</p>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative">
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">País</label>
                                <button type="button" @click="openCountry = !openCountry" 
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-left flex justify-between items-center hover:border-indigo-400 transition-all">
                                    <span class="flex items-center gap-3 font-semibold text-slate-700">
                                        <span x-text="countries.find(c => c.id == selectedCountry)?.emoji"></span>
                                        <span x-text="countries.find(c => c.id == selectedCountry)?.name"></span>
                                    </span>
                                    <x-heroicon-s-chevron-down class="w-5 h-5 text-slate-400" />
                                </button>
                                <input type="hidden" name="country_id" :value="selectedCountry" required>
                                
                                <div x-show="openCountry" @click.outside="openCountry = false" class="absolute z-[100] mt-2 w-full bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                                    <div class="p-3 bg-slate-50 border-b">
                                        <input type="text" x-model="searchCountry" placeholder="Buscar país..." class="w-full border-none bg-transparent text-sm focus:ring-0">
                                    </div>
                                    <div class="max-h-60 overflow-y-auto">
                                        <template x-for="country in filteredCountries" :key="country.id">
                                            <button type="button" @click="updateRegionalData(country.id)" class="w-full text-left px-5 py-3 text-sm hover:bg-indigo-50 flex items-center gap-3">
                                                <span x-text="country.emoji"></span>
                                                <span x-text="country.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Estado / Provincia</label>
                                <button type="button" @click="openState = !openState" :disabled="isLoading"
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-left flex justify-between items-center disabled:opacity-50 transition-all hover:border-indigo-400">
                                    <span class="font-semibold text-slate-700 text-sm" x-text="isLoading ? 'Cargando...' : (states.find(s => s.id == selectedState)?.name || 'Seleccionar...')"></span>
                                    <x-heroicon-s-chevron-down class="w-5 h-5 text-slate-400" />
                                </button>
                                <input type="hidden" name="state_id" :value="selectedState">

                                <div x-show="openState" @click.outside="openState = false" class="absolute z-[100] mt-2 w-full bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                                    <div class="p-3 bg-slate-50 border-b">
                                        <input type="text" x-model="searchState" placeholder="Buscar provincia..." class="w-full border-none bg-transparent text-sm focus:ring-0">
                                    </div>
                                    <div class="max-h-60 overflow-y-auto">
                                        <template x-for="state in filteredStates" :key="state.id">
                                            <button type="button" @click="selectedState = state.id; openState = false; searchState = ''" class="w-full text-left px-5 py-3 text-sm hover:bg-indigo-50">
                                                <span x-text="state.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-1">
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Ciudad</label>
                                <x-text-input name="ciudad" type="text" class="w-full" placeholder="Ej: Bonao" value="{{ $config->ciudad ?? '' }}" ::disabled="isLoading" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Dirección</label>
                                <x-text-input name="direccion" type="text" class="w-full" placeholder="Calle, edificio, apto..." value="{{ $config->direccion ?? '' }}" ::disabled="isLoading" />
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100" :class="isLoading ? 'opacity-50' : ''">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 transition-all">
                                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Moneda Local</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-blue-700" x-text="currency"></span>
                                    </div>
                                </div>
                                <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-4 transition-all">
                                    <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1">Prefijo</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-indigo-700" x-text="`+${phoneCode}`"></span>
                                    </div>
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 transition-all overflow-hidden">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Zona Horaria</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-700 truncate" x-text="timezone"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center gap-4">
                        <span class="flex-none w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">2</span>
                        <h2 class="font-bold text-slate-800 text-lg">Canales de Contacto</h2>
                    </div>
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Email Corporativo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <x-heroicon-s-envelope class="w-5 h-5 text-slate-400" />
                                </div>
                                <x-text-input name="email" type="email" class="w-full pl-11" placeholder="admin@empresa.com" value="{{ $config->email ?? '' }}" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Teléfono</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-4 rounded-l-2xl border border-r-0 border-slate-200 bg-slate-50 text-slate-500 font-bold text-xs" x-text="`+${phoneCode}`"></span>
                                <x-text-input name="telefono" type="text" class="flex-1 rounded-l-none" placeholder="809 000 0000" value="{{ $config->telefono ?? '' }}" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center gap-4">
                        <span class="flex-none w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">3</span>
                        <h3 class="font-bold text-slate-800 text-lg">Identidad e Información Legal</h3>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
                            <div class="md:col-span-4 flex flex-col items-center border-b md:border-b-0 md:border-r border-slate-100 pb-8 md:pb-0 md:pr-10">
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-4 self-start">Logo de la Empresa</label>
                                <div class="w-40 h-40 rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden mb-4 relative group transition-all hover:border-indigo-300">
                                    <template x-if="logoPreview">
                                        <img :src="logoPreview" class="object-contain w-full h-full p-2">
                                    </template>
                                    <template x-if="!logoPreview">
                                        <x-heroicon-s-photo class="w-16 h-16 text-slate-200" />
                                    </template>
                                    <div x-show="logoPreview" class="absolute inset-0 bg-indigo-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                        <span class="text-[10px] text-white font-black uppercase tracking-widest">Cambiar Imagen</span>
                                    </div>
                                </div>
                                <input type="file" name="logo" accept="image/*" @change="updateLogoPreview"
                                    class="text-[10px] text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer w-full" />
                                <p class="mt-3 text-[9px] text-slate-400 italic text-center leading-tight">Sugerido: PNG/SVG fondo transparente (200x200px)</p>
                            </div>

                            <div class="md:col-span-8 space-y-8">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre Comercial</label>
                                        <x-text-input name="nombre_empresa" class="w-full" value="{{ $config->nombre_empresa ?? '' }}" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1">Identificación Fiscal</label>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <select name="tax_identifier_type_id" 
                                                class="w-full sm:w-32 border-slate-200 rounded-2xl text-[11px] bg-slate-50 font-bold text-slate-700" 
                                                x-model="selectedTaxType" :disabled="isLoading" required>
                                                <option value="" disabled x-show="!selectedTaxType">Tipo</option>
                                                <template x-for="type in taxTypes" :key="type.id">
                                                    <option :value="type.id" x-text="type.code" :selected="type.id == selectedTaxType"></option>
                                                </template>
                                            </select>
                                            <x-text-input name="tax_id" class="flex-1" placeholder="Número de identificación" value="{{ $config->tax_id ?? '' }}" ::disabled="isLoading" />
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-indigo-50/30 rounded-3xl p-6 border border-indigo-100/50">
                                    <h4 class="text-[11px] font-black text-indigo-600 uppercase mb-5 flex items-center gap-2 tracking-widest">
                                        <x-heroicon-s-receipt-percent class="w-4 h-4" />
                                        Impuesto Principal
                                    </h4>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div class="sm:col-span-2 lg:col-span-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase mb-1">Nombre (IVA, ITBIS...)</label>
                                            <x-text-input name="impuesto_nombre" value="{{ $config->impuesto->nombre ?? '' }}" class="w-full bg-white" />
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-500 uppercase mb-1">Tipo</label>
                                            <select name="impuesto_tipo" class="w-full border-slate-200 rounded-2xl bg-white text-xs font-bold text-slate-700">
                                                <option value="porcentaje" {{ ($config->impuesto?->tipo ?? '') == 'porcentaje' ? 'selected' : '' }}>Porcentaje %</option>
                                                <option value="fijo" {{ ($config->impuesto?->tipo ?? '') == 'fijo' ? 'selected' : '' }}>Monto Fijo $</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-500 uppercase mb-1">Valor</label>
                                            <x-text-input name="impuesto_valor" type="number" step="0.01" value="{{ $config->impuesto->valor ?? '' }}" class="w-full bg-white" />
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-start gap-3 bg-white/60 p-4 rounded-2xl border border-indigo-100">
                                        <input type="checkbox" name="impuesto_incluido" value="1" 
                                            {{ ($config->impuesto?->es_incluido ?? false) ? 'checked' : '' }}
                                            class="mt-1 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <label class="text-xs font-semibold text-slate-600 leading-snug">
                                            El precio de los productos ya incluye este impuesto. <span class="block text-[10px] font-normal text-slate-400 italic">Activa esto si tus precios de venta son finales.</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mt-8">
                    <div class="p-6 border-b border-slate-100 flex items-center gap-4">
                        <span class="flex-none w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">4</span>
                        <h3 class="font-bold text-slate-800 text-lg">Regulación Fiscal (NCF)</h3>
                    </div>

                    <div class="p-8">
                        <div class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <div class="space-y-1">
                                <h4 class="text-sm font-bold text-slate-700">Activar Comprobantes Fiscales (NCF/e-NCF)</h4>
                                <p class="text-xs text-slate-500 max-w-md">
                                    Al activar esta opción, el sistema exigirá secuencias válidas de la DGII para cada venta. Si se desactiva, las ventas se generarán como documentos internos sin valor fiscal.
                                </p>
                            </div>
                            
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="usa_ncf" value="1" class="sr-only peer" 
                                    x-model="usaNcf" :checked="usaNcf">
                                <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div x-show="usaNcf" x-transition class="mt-4 flex items-start gap-3 bg-amber-50 p-4 rounded-2xl border border-amber-100">
                            <x-heroicon-s-exclamation-circle class="w-5 h-5 text-amber-500 mt-0.5" />
                            <p class="text-xs text-amber-700 leading-snug">
                                <strong>Modo Estricto Activo:</strong> Asegúrese de tener secuencias configuradas en el módulo de NCF. El sistema bloqueará las ventas si las secuencias están agotadas o vencidas.
                            </p>
                        </div>
                    </div>
                </section>

                <div class="sticky bottom-6 bg-white/80 backdrop-blur-md border border-slate-200 p-4 rounded-3xl shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4 z-[40]">
                    <button type="button" x-on:click="$dispatch('open-modal', 'confirm-discard')"
                        class="w-full sm:w-auto px-6 py-3 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                        Descartar cambios
                    </button>

                    <x-primary-button class="w-full sm:w-auto bg-indigo-600 px-10 py-4 rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all" ::disabled="isLoading">
                        <span x-show="!isLoading" class="flex items-center gap-2">
                            <x-heroicon-s-cloud-arrow-up class="w-5 h-5" />
                            Guardar Configuración
                        </span>
                        <span x-show="isLoading">Procesando...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>

        <x-modal name="confirm-discard" :show="false" maxWidth="md">
            <div class="p-8">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-50 rounded-full mb-4">
                    <x-heroicon-s-exclamation-triangle class="w-8 h-8 text-red-500" />
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-bold text-slate-800 mb-2">¿Descartar cambios?</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">
                        Se perderán todos los datos modificados. Los valores volverán a su estado original guardado en el servidor.
                    </p>
                </div>
                <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center py-3">
                        Continuar editando
                    </x-secondary-button>
                    <x-danger-button @click="window.location.reload()" class="w-full sm:w-auto justify-center py-3">
                        Sí, descartar todo
                    </x-danger-button>
                </div>
            </div>
        </x-modal>
    </div>
</x-config-layout>