<x-app-layout>
    <div class="min-h-screen py-12 px-4"
        x-cloak
        x-data="{
            provinces: {{ $provinces->toJson() }},
            municipalities: {{ $municipalities->toJson() }},
            taxTypes: {{ $taxTypes->toJson() }},

            selectedProvincia: '{{ old('provincia_id', $config->provincia_id ?? '') }}',
            selectedMunicipio: '{{ old('municipio_id', $config->municipio_id ?? '') }}',
            selectedTaxType: '{{ old('tax_identifier_type', $config->tax_identifier_type?->value ?? '') }}',

            logoPreview: '{{ $config?->logo ? asset('storage/'.$config->logo) : '' }}',
            currency: '{{ config('regional.currency') }}',
            timezone: '{{ config('regional.timezone') }}',

            activeTab: 'ubicacion',

            updateLogoPreview(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => { this.logoPreview = e.target.result; };
                    reader.readAsDataURL(file);
                }
            },

            get municipiosDeProvincia() {
                return this.municipalities.filter(m => m.province_id == this.selectedProvincia);
            }
        }">

        <div class="max-w-4xl mx-auto">

            {{-- Banner de Plan — visible siempre, fuera de los tabs, para que el dueño
                 sepa qué Plan tiene activo sin tener que navegar a Funcionalidades del
                 Sistema. Solo lectura acá: el Plan en sí no se cambia desde esta pantalla. --}}
            @php $plan = current_plan(); @endphp
            <div class="mb-6 bg-gradient-to-r from-zertix-primary to-zertix-primary-dark rounded-3xl shadow-lg shadow-zertix-primary/20 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="flex-none w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center">
                        <x-heroicon-s-sparkles class="w-6 h-6 text-white" />
                    </span>
                    <div>
                        <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest">Plan Actual</p>
                        <p class="text-xl font-black text-white leading-tight">{{ $plan?->name ?? 'Sin Plan asignado' }}</p>
                        @if ($plan?->description)
                            <p class="text-xs text-white/80 mt-0.5">{{ $plan->description }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ route('configuration.features') }}"
                    class="w-full sm:w-auto text-center bg-white/15 hover:bg-white/25 text-white text-xs font-bold uppercase tracking-wide px-5 py-3 rounded-2xl transition-colors">
                    Gestionar Funcionalidades
                </a>
            </div>

            <form method="POST" action="{{ route('configuration.general.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Navegación por tabs — reemplaza el scroll largo de 3 secciones
                     apiladas por paneles independientes (mejora UI/UX pedida). --}}
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="flex border-b border-slate-100 overflow-x-auto">
                        <button type="button" @click="activeTab = 'ubicacion'"
                            :class="activeTab === 'ubicacion' ? 'text-zertix-primary-dark border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                            class="flex items-center gap-2 px-6 py-4 text-sm font-bold border-b-2 transition-colors whitespace-nowrap shrink-0">
                            <x-heroicon-s-map-pin class="w-4 h-4" />
                            Ubicación de Operación
                        </button>
                        <button type="button" @click="activeTab = 'contacto'"
                            :class="activeTab === 'contacto' ? 'text-zertix-primary-dark border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                            class="flex items-center gap-2 px-6 py-4 text-sm font-bold border-b-2 transition-colors whitespace-nowrap shrink-0">
                            <x-heroicon-s-phone class="w-4 h-4" />
                            Canales de Contacto
                        </button>
                        <button type="button" @click="activeTab = 'identidad'"
                            :class="activeTab === 'identidad' ? 'text-zertix-primary-dark border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                            class="flex items-center gap-2 px-6 py-4 text-sm font-bold border-b-2 transition-colors whitespace-nowrap shrink-0">
                            <x-heroicon-s-identification class="w-4 h-4" />
                            Identidad y Legal
                        </button>
                    </div>

                    {{-- PANEL 1: Ubicación de Operación --}}
                    <div x-show="activeTab === 'ubicacion'" x-cloak>
                        <div class="p-8 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Provincia</label>
                                    {{-- Select nativo (antes era un dropdown Alpine con buscador propio,
                                         pero se cortaba dentro del panel de tabs por el overflow del
                                         contenedor — un <select> nativo no tiene ese problema). --}}
                                    <select name="provincia_id" x-model="selectedProvincia"
                                        @change="selectedMunicipio = ''" required
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary">
                                        <option value="" disabled>Seleccionar...</option>
                                        <template x-for="provincia in provinces" :key="provincia.id">
                                            <option :value="provincia.id" x-text="provincia.name"></option>
                                        </template>
                                    </select>
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
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold text-slate-700 focus:border-zertix-primary">
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

                            <div class="pt-6 border-t border-slate-100">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-zertix-primary/5 border border-zertix-primary/20 rounded-2xl p-4 transition-all">
                                        <p class="text-[10px] font-bold text-zertix-primary-dark uppercase tracking-widest mb-1">Moneda Local</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg font-bold text-zertix-primary-dark" x-text="currency"></span>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 transition-all overflow-hidden">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Zona Horaria</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-700 truncate" x-text="timezone"></span>
                                        </div>
                                    </div>
                                    {{-- dias_gracia_mora alimenta Client::esMoroso() (§11.4) — sin sentido
                                         editarlo con sales.receivables apagado (núcleo flexible, REQ-10.5).
                                         Deshabilitado, no oculto: nullable en el request, así que disabled
                                         es seguro acá (no rompe la validación al no enviarse). --}}
                                    <div class="bg-amber-50/50 border border-amber-100 rounded-2xl p-4 transition-all">
                                        <label class="text-[10px] font-bold text-amber-500 uppercase tracking-widest mb-1 block">Días de Gracia (Mora)</label>
                                        <input type="number" name="dias_gracia_mora" min="0" value="{{ old('dias_gracia_mora', $config->dias_gracia_mora ?? 0) }}"
                                            @disabled(! module_enabled('sales.receivables'))
                                            class="w-full bg-transparent border-0 p-0 text-lg font-bold {{ module_enabled('sales.receivables') ? 'text-amber-700' : 'text-gray-400 cursor-not-allowed' }} focus:ring-0" />
                                        @unless (module_enabled('sales.receivables'))
                                            <p class="text-[9px] text-amber-500 italic mt-1">Se activa junto a Cuentas por Cobrar.</p>
                                        @endunless
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PANEL 2: Canales de Contacto --}}
                    <div x-show="activeTab === 'contacto'" x-cloak>
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
                    </div>

                    {{-- PANEL 3: Identidad e Información Legal --}}
                    <div x-show="activeTab === 'identidad'" x-cloak>
                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
                                <div class="md:col-span-4 flex flex-col items-center border-b md:border-b-0 md:border-r border-slate-100 pb-8 md:pb-0 md:pr-10">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-4 self-start">Logo de la Empresa</label>
                                    <div class="w-40 h-40 rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden mb-4 relative group transition-all hover:border-zertix-primary">
                                        <template x-if="logoPreview">
                                            <img :src="logoPreview" class="object-contain w-full h-full p-2">
                                        </template>
                                        <template x-if="!logoPreview">
                                            <x-heroicon-s-photo class="w-16 h-16 text-slate-200" />
                                        </template>
                                        <div x-show="logoPreview" class="absolute inset-0 bg-zertix-secondary/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                            <span class="text-[10px] text-white font-black uppercase tracking-widest">Cambiar Imagen</span>
                                        </div>
                                    </div>
                                    <input type="file" name="logo" accept="image/*" @change="updateLogoPreview"
                                        class="text-[10px] text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-zertix-primary/10 file:text-zertix-primary-dark hover:file:bg-zertix-primary/20 cursor-pointer w-full" />
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

                                    {{-- "Impuesto Principal" (tasa única global) se eliminó (Fase 5, REQ-5.1) —
                                         los impuestos ahora se asignan por producto (ITBIS 18%/16%, Exento,
                                         ISC) desde config/impuestos.php + product_taxes, no acá. --}}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sección "Regulación Fiscal (NCF)" eliminada (REQ-10.9) — el toggle
                         ncf_enabled quedó duplicado con el módulo sales.ncf, que ahora se
                         administra desde Configuración → Funcionalidades del Sistema
                         (REQ-10.6). Tener el mismo flag editable en dos pantallas invitaba a
                         que quedaran desincronizados; el patrón "toggle aplica al instante"
                         que usaba esta sección era además el que REQ-10.6 explícitamente
                         decidió no replicar en la pantalla nueva. --}}
                </div>

                <div class="sticky bottom-6 mt-8 bg-white/80 backdrop-blur-md border border-slate-200 p-4 rounded-3xl shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4 z-[40]">
                    <button type="button" x-on:click="$dispatch('open-modal', 'confirm-discard')"
                        class="w-full sm:w-auto px-6 py-3 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                        Descartar cambios
                    </button>

                    <x-ui.button type="submit" variant="primary" :hoverEffect="true" class="w-full sm:w-auto px-10 py-4 rounded-2xl shadow-lg">
                        <span class="flex items-center gap-2">
                            <x-heroicon-s-cloud-arrow-up class="w-5 h-5" />
                            Guardar Configuración
                        </span>
                    </x-ui.button>
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
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center py-3">
                        Continuar editando
                    </x-ui.button>
                    <x-ui.button type="submit" variant="error" @click="window.location.reload()" class="w-full sm:w-auto justify-center py-3">
                        Sí, descartar todo
                    </x-ui.button>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
