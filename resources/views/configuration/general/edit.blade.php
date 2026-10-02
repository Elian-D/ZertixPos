{{-- Configuración General — formulario estilo Filament (/filament-form): secciones
     apiladas a lo ancho con x-ui.infolist.section (mismo lenguaje que los show) y la
     grilla de campos dentro de cada una.
     Recibe $config (ConfiguracionGeneral::actual()), $provinces, $municipalities
     (precargados: el filtro provincia → municipio es de Alpine, sin AJAX) y $taxTypes. --}}
@php
    $plan = current_plan();
    $receivablesOn = module_enabled('sales.receivables');
@endphp

<x-app-layout title="Configuración General">
    <div class="p-4 md:p-6"
        x-data="{
            provinces: {{ $provinces->toJson() }},
            municipalities: {{ $municipalities->toJson() }},
            taxTypes: {{ $taxTypes->toJson() }},
            selectedProvincia: '{{ old('provincia_id', $config->provincia_id ?? '') }}',
            selectedMunicipio: '{{ old('municipio_id', $config->municipio_id ?? '') }}',
            selectedTaxType: '{{ old('tax_identifier_type', $config->tax_identifier_type?->value ?? '') }}',
            get municipiosDeProvincia() {
                return this.municipalities.filter(m => m.province_id == this.selectedProvincia);
            },
        }">

        <x-ui.page-header title="Configuración general" description="Datos de la empresa que aparecen en facturas, tickets y reportes." />

        <form method="POST" action="{{ route('configuration.general.update') }}" enctype="multipart/form-data"
              class="mt-6 flex flex-col gap-6">
            @csrf
            @method('PUT')

            {{-- Identidad --}}
            <x-ui.infolist.section title="Identidad y datos fiscales" icon="heroicon-o-identification" :cols="0"
                description="Cómo se identifica la empresa en los documentos que emite.">
                <div class="grid grid-cols-1 md:grid-cols-[auto_1fr] gap-x-8 gap-y-6">
                    {{-- Logo --}}
                    <div>
                        <span class="block text-sm font-medium text-gray-900 mb-2">Logo</span>
                        <div class="flex items-start gap-3">
                            @if($config?->logo)
                                <div class="text-center">
                                    <img src="{{ tenant_asset($config->logo) }}" alt="Logo actual"
                                         class="w-28 h-28 rounded-xl border border-gray-100 object-contain bg-white p-2">
                                    <span class="mt-1 block text-xs text-gray-400">Actual</span>
                                </div>
                            @endif
                            <x-ui.forms.file-input name="logo" accept="image/*" :dropzone="true" :preview="true" size="md"
                                hint="PNG o SVG, hasta 2 MB" :error="$errors->first('logo')" />
                        </div>
                    </div>

                    {{-- Nombre e identificación --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-5 content-start">
                        <div class="sm:col-span-3">
                            <x-ui.forms.input label="Nombre comercial" name="nombre_empresa" required
                                value="{{ old('nombre_empresa', $config->nombre_empresa ?? '') }}"
                                hint="Aparece en el encabezado de facturas y tickets."
                                :error="$errors->first('nombre_empresa')" />
                        </div>
                        {{-- :selected en cada option: las opciones salen de x-for DESPUÉS de que
                             x-model fija el valor; sin esto el valor guardado no se marca. --}}
                        <x-ui.forms.select label="Tipo de identificación" name="tax_identifier_type"
                            x-model="selectedTaxType" placeholder="Seleccionar..."
                            :error="$errors->first('tax_identifier_type')">
                            <template x-for="type in taxTypes" :key="type.value">
                                <option :value="type.value" x-text="type.label" :selected="type.value == selectedTaxType"></option>
                            </template>
                        </x-ui.forms.select>
                        <div class="sm:col-span-2">
                            <x-ui.forms.input label="Número de identificación" name="tax_id" placeholder="Ej. 131-12345-6"
                                value="{{ old('tax_id', $config->tax_id ?? '') }}"
                                :error="$errors->first('tax_id')" />
                        </div>
                    </div>
                </div>
            </x-ui.infolist.section>

            {{-- Contacto --}}
            <x-ui.infolist.section title="Contacto" icon="heroicon-o-phone" :cols="0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.input label="Email" name="email" type="email" icon-left="heroicon-s-envelope"
                        placeholder="admin@empresa.com"
                        value="{{ old('email', $config->email ?? '') }}"
                        :error="$errors->first('email')" />
                    <x-ui.forms.input label="Teléfono" name="telefono" icon-left="heroicon-s-phone"
                        placeholder="809 000 0000" hint="Solo el número local, sin +1."
                        value="{{ old('telefono', $config->telefono ?? '') }}"
                        :error="$errors->first('telefono')" />
                </div>
            </x-ui.infolist.section>

            {{-- Ubicación --}}
            <x-ui.infolist.section title="Ubicación" icon="heroicon-o-map-pin" :cols="0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <x-ui.forms.select label="Provincia" name="provincia_id" required
                        x-model="selectedProvincia" @change="selectedMunicipio = ''"
                        placeholder="Seleccionar..."
                        :error="$errors->first('provincia_id')">
                        <template x-for="provincia in provinces" :key="provincia.id">
                            <option :value="provincia.id" x-text="provincia.name" :selected="provincia.id == selectedProvincia"></option>
                        </template>
                    </x-ui.forms.select>

                    <x-ui.forms.select label="Municipio" name="municipio_id"
                        x-model="selectedMunicipio" x-bind:disabled="! selectedProvincia"
                        placeholder="Sin especificar"
                        :error="$errors->first('municipio_id')">
                        <template x-for="municipio in municipiosDeProvincia" :key="municipio.id">
                            <option :value="municipio.id" x-text="municipio.name" :selected="municipio.id == selectedMunicipio"></option>
                        </template>
                    </x-ui.forms.select>

                    <div class="sm:col-span-2">
                        <x-ui.forms.input label="Dirección" name="direccion" placeholder="Calle, número, sector..."
                            value="{{ old('direccion', $config->direccion ?? '') }}"
                            :error="$errors->first('direccion')" />
                    </div>
                </div>
            </x-ui.infolist.section>

            {{-- Parámetros regionales --}}
            <x-ui.infolist.section title="Parámetros regionales" icon="heroicon-o-globe-americas" :cols="0"
                description="Moneda y zona horaria vienen fijas de la instalación.">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-5">
                    <x-ui.forms.input label="Moneda" name="currency_display" value="{{ config('regional.currency') }}" disabled />
                    <x-ui.forms.input label="Zona horaria" name="timezone_display" value="{{ config('regional.timezone') }}" disabled />
                    {{-- Alimenta Client::esMoroso(); sin sentido con Cuentas por Cobrar apagado.
                         disabled no se envía y la regla es nullable: no se pisa el valor guardado. --}}
                    <x-ui.forms.input label="Días de gracia para mora" name="dias_gracia_mora" type="number" min="0"
                        value="{{ old('dias_gracia_mora', $config->dias_gracia_mora ?? 0) }}"
                        :disabled="! $receivablesOn"
                        :hint="$receivablesOn ? 'Días tras el vencimiento antes de marcar al cliente como moroso.' : 'Se activa junto a Cuentas por Cobrar.'"
                        :error="$errors->first('dias_gracia_mora')" />
                </div>
            </x-ui.infolist.section>

            {{-- Plan (solo lectura) --}}
            <x-ui.infolist.section title="Plan actual" icon="heroicon-o-sparkles" :cols="0">
                <x-slot:headerActions>
                    <x-ui.button href="{{ route('configuration.features') }}" variant="secondary" appearance="outline" size="sm"
                        iconLeft="heroicon-s-squares-2x2">
                        Gestionar funcionalidades
                    </x-ui.button>
                </x-slot:headerActions>
                <p class="text-sm font-semibold text-gray-900">{{ $plan?->name ?? 'Sin plan asignado' }}</p>
                @if($plan?->description)
                    <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>
                @endif
            </x-ui.infolist.section>

            {{-- Acciones --}}
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('open-modal', 'confirm-discard')">
                    Descartar cambios
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Guardar configuración
                </x-ui.button>
            </div>
        </form>

        <x-modal name="confirm-discard" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900">¿Descartar cambios?</h3>
                <p class="mt-1 text-sm text-gray-500">Se perderán los datos modificados y se recargarán los valores guardados.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Seguir editando</x-ui.button>
                    <x-ui.button variant="error" x-on:click="window.location.reload()">Descartar</x-ui.button>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
