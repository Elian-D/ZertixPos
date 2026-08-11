<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-10">
    <h1 class="text-2xl font-bold text-gray-900 text-center">Información de la Empresa</h1>
    <p class="mt-2 text-sm text-gray-500 text-center">Configure los datos fiscales y de contacto de su negocio.</p>

    <form wire:submit.prevent="nextStep" class="mt-8 space-y-6">
        {{-- LOGO --}}
        <div x-data="{ preview: null }" class="border-2 border-dashed border-gray-200 rounded-2xl p-8 flex flex-col items-center text-center hover:border-zertix-primary transition-colors">
            <label class="cursor-pointer flex flex-col items-center">
                <template x-if="!preview">
                    <span class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                        <x-heroicon-s-photo class="w-6 h-6 text-gray-400" />
                    </span>
                </template>
                <template x-if="preview">
                    <img :src="preview" class="w-20 h-20 rounded-full object-cover mb-3" />
                </template>
                <span class="font-bold text-gray-800 text-sm">Subir Logo</span>
                <span class="text-xs text-gray-400 mt-1">JPG, PNG o SVG. Tamaño recomendado: 512×512px.</span>
                <input type="file" wire:model="logo" accept="image/*" class="hidden"
                    @change="preview = URL.createObjectURL($event.target.files[0])" />
            </label>
        </div>
        @error('logo') <p class="text-xs text-red-600 text-center -mt-4">{{ $message }}</p> @enderror

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Nombre de la Empresa *</label>
                <input type="text" wire:model="nombreEmpresa" placeholder="Ej. Comercial López"
                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                @error('nombreEmpresa') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Tipo de documento explícito — antes se inferia del largo del numero
                 (9 digitos = RNC, 11 = Cedula) y ese resultado nunca se guardaba
                 realmente en tax_identifier_type. Ahora es un campo propio, en el
                 orden pedido: nombre, tipo, numero. --}}
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Tipo de Documento *</label>
                <select wire:model="taxIdentifierType"
                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm">
                    <option value="">Seleccione un tipo</option>
                    @foreach (\App\Enums\TaxIdentifierType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('taxIdentifierType') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Número de Documento *</label>
                <input type="text" wire:model="taxId" placeholder="131-XXXXX-X"
                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                @error('taxId') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Teléfono *</label>
                <input type="text" wire:model="telefono" placeholder="(809) XXX-XXXX"
                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                @error('telefono') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Correo Corporativo</label>
                <input type="email" wire:model="email" placeholder="contacto@empresa.com"
                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                @error('email') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Cascada provincia→municipio 100% client-side (Alpine + @entangle) —
                 sin request de red, mismo patrón que REQ-06.9 (Fase 6). La versión
                 anterior usaba wire:model.live en ambos selects y un
                 updatedProvinciaId() server-side; eso abría una condición de carrera
                 real entre los dos round-trips donde el municipio elegido se perdía
                 en silencio (bug reportado y reproducido). Entangle es puramente
                 client-side hasta que el form se envía — no hay dos requests que
                 puedan pisarse. --}}
            <div x-data="{
                    municipalities: {{ \Illuminate\Support\Js::from($this->municipalities) }},
                    provinciaId: @entangle('provinciaId'),
                    municipioId: @entangle('municipioId'),
                    get filtered() { return this.municipalities.filter(m => m.province_id == this.provinciaId); },
                }" class="contents">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Provincia *</label>
                    <select x-model="provinciaId" @change="municipioId = null"
                        class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm">
                        <option value="">Seleccione una provincia</option>
                        @foreach ($this->provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('provinciaId') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Municipio</label>
                    <select x-model="municipioId" :disabled="!provinciaId"
                        class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm disabled:bg-gray-50 disabled:text-gray-400">
                        <option value="">Seleccione un municipio</option>
                        <template x-for="m in filtered" :key="m.id">
                            <option :value="m.id" x-text="m.name"></option>
                        </template>
                    </select>
                    @error('municipioId') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Dirección Física *</label>
                <input type="text" wire:model="direccion" placeholder="Calle, Número, Sector"
                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                @error('direccion') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Mobile: 2 columnas parejas (antes se apretaban en una sola fila) — desktop
             vuelve al layout natural, Atrás como link chico a la izquierda. --}}
        <div class="grid grid-cols-2 gap-3 pt-2 sm:flex sm:items-center sm:justify-between">
            <button type="button" wire:click="prevStep"
                class="border border-gray-200 sm:border-0 rounded-xl sm:rounded-none py-3 sm:py-0 text-sm font-semibold text-gray-500 hover:text-gray-700 flex items-center justify-center sm:justify-start gap-1">
                <x-heroicon-s-arrow-left class="w-4 h-4" /> Atrás
            </button>
            <button type="submit"
                class="bg-zertix-primary hover:bg-zertix-primary-dark text-white font-bold py-3.5 px-4 sm:px-8 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm sm:text-base">
                Siguiente: Seleccionar Plan
                <x-heroicon-s-arrow-right class="w-4 h-4 flex-shrink-0" />
            </button>
        </div>
    </form>
</div>
