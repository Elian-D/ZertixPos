<x-config-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4"
        x-cloak
        x-data="{
            provinces: {{ $provinces->toJson() }},
            municipalities: {{ $municipalities->toJson() }},
            usaNcf: {{ old('ncf_enabled', module_enabled('sales.ncf')) ? 'true' : 'false' }},
            taxTypes: {{ $taxTypes->toJson() }},

            searchProvincia: '',
            openProvincia: false,

            selectedProvincia: '{{ old('provincia_id', $config->provincia_id ?? '') }}',
            selectedMunicipio: '{{ old('municipio_id', $config->municipio_id ?? '') }}',
            selectedTaxType: '{{ old('tax_identifier_type', $config->tax_identifier_type?->value ?? '') }}',
            selectedImpuesto: '{{ old('impuesto_id', $config->impuesto_id ?? '') }}',

            logoPreview: '{{ $config?->logo ? asset('storage/'.$config->logo) : '' }}',
            currency: '{{ config('regional.currency') }}',
            timezone: '{{ config('regional.timezone') }}',

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

            get filteredProvincias() {
                let s = this.formatSearch(this.searchProvincia);
                return this.provinces.filter(p => this.formatSearch(p.name).includes(s));
            },

            get municipiosDeProvincia() {
                return this.municipalities.filter(m => m.province_id == this.selectedProvincia);
            },

            selectProvincia(id) {
                this.selectedProvincia = id;
                this.openProvincia = false;
                this.searchProvincia = '';
                this.selectedMunicipio = '';
            }
        }">

        <div class="max-w-4xl mx-auto">
            <x-ui.toasts />

            <form method="POST" action="{{ route('configuration.general.update') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-visible relative">
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
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Provincia</label>
                                <button type="button" @click="openProvincia = !openProvincia"
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-left flex justify-between items-center hover:border-indigo-400 transition-all">
                                    <span class="font-semibold text-slate-700 text-sm" x-text="provinces.find(p => p.id == selectedProvincia)?.name || 'Seleccionar...'"></span>
                                    <x-heroicon-s-chevron-down class="w-5 h-5 text-slate-400" />
                                </button>
                                <input type="hidden" name="provincia_id" :value="selectedProvincia" required>

                                <div x-show="openProvincia" @click.outside="openProvincia = false" class="absolute z-[100] mt-2 w-full bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                                    <div class="p-3 bg-slate-50 border-b">
                                        <input type="text" x-model="searchProvincia" placeholder="Buscar provincia..." class="w-full border-none bg-transparent text-sm focus:ring-0">
                                    </div>
                                    <div class="max-h-60 overflow-y-auto">
                                        <template x-for="provincia in filteredProvincias" :key="provincia.id">
                                            <button type="button" @click="selectProvincia(provincia.id)" class="w-full text-left px-5 py-3 text-sm hover:bg-indigo-50">
                                                <span x-text="provincia.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Municipio</label>
                                {{-- x-init + $nextTick: las <option> las genera x-for dentro del propio
                                     <select>, y Alpine aplica x-model ANTES de que ese x-for termine de
                                     renderizarlas en el primer render — el municipio guardado en BD
                                     nunca quedaba seleccionado visualmente (aunque sí estaba guardado,
                                     bug reportado y confirmado por consulta directa a la BD). Forzamos
                                     la sincronización una vez el DOM ya tiene las opciones montadas. --}}
                                <select name="municipio_id" x-model="selectedMunicipio"
                                    x-init="$nextTick(() => { $el.value = selectedMunicipio })"
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-indigo-400">
                                    <option value="">Sin especificar</option>
                                    <template x-for="municipio in municipiosDeProvincia" :key="municipio.id">
                                        <option :value="municipio.id" x-text="municipio.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Dirección</label>
                                <x-text-input name="direccion" type="text" class="w-full" placeholder="Calle, edificio, apto..." value="{{ $config->direccion ?? '' }}" />
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 transition-all">
                                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Moneda Local</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-blue-700" x-text="currency"></span>
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
                                <span class="inline-flex items-center px-4 rounded-l-2xl border border-r-0 border-slate-200 bg-slate-50 text-slate-500 font-bold text-xs">+1</span>
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
                                            <select name="tax_identifier_type"
                                                class="w-full sm:w-32 border-slate-200 rounded-2xl text-[11px] bg-slate-50 font-bold text-slate-700"
                                                x-model="selectedTaxType">
                                                <option value="" disabled x-show="!selectedTaxType">Tipo</option>
                                                <template x-for="type in taxTypes" :key="type.value">
                                                    <option :value="type.value" x-text="type.label" :selected="type.value == selectedTaxType"></option>
                                                </template>
                                            </select>
                                            <x-text-input name="tax_id" class="flex-1" placeholder="Número de identificación" value="{{ $config->tax_id ?? '' }}" />
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
                                <input type="checkbox" name="ncf_enabled" value="1" class="sr-only peer"
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

                    <x-primary-button class="w-full sm:w-auto bg-indigo-600 px-10 py-4 rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all">
                        <span class="flex items-center gap-2">
                            <x-heroicon-s-cloud-arrow-up class="w-5 h-5" />
                            Guardar Configuración
                        </span>
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