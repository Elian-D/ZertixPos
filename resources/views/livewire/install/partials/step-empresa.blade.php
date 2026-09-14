{{-- Título/subtítulo viven en install-wizard.blade.php (fuera de la card,
     ver InstallWizard::STEP_META) — rediseño 2026-09-05. --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-7 sm:p-9">
    <form wire:submit.prevent="nextStep" class="space-y-6">
        {{-- Logo + Nombre/Subdominio en una sola fila (mockup Stitch "Instalación
             Paso 2") — el logo es una caja cuadrada angosta (dropzone, ver
             docs/ui/forms.md) al lado, no un campo más apilado arriba. --}}
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-start">
            <div class="sm:col-span-4">
                {{-- :dropzone="true" (docs/ui/forms.md, nuevo en el rediseño
                     2026-09-05) — logo/foto de perfil, no un archivo genérico:
                     caja cuadrada con ícono centrado en vez de la fila tipo
                     campo de texto. :preview="true" llena la caja con la
                     imagen elegida. --}}
                <x-ui.forms.file-input
                    label="Logotipo"
                    name="logo"
                    wire:model="logo"
                    accept="image/*"
                    :dropzone="true"
                    :preview="true"
                    size="lg"
                    hint="PNG, JPG hasta 2MB"
                    :error="$errors->first('logo')"
                />
            </div>

            <div class="sm:col-span-8 space-y-5">
                <x-ui.forms.input
                    label="Nombre de la Empresa"
                    name="nombreEmpresa"
                    wire:model.live.debounce.500ms="nombreEmpresa"
                    placeholder="Ej. Comercial López"
                    :error="$errors->first('nombreEmpresa')"
                    required
                />

                {{-- Subdominio (REQ-4.6/4.7) — define el Domain real. Auto-sugerido
                     desde el nombre de la empresa (patrón Odoo: "Agua Discovery" →
                     "agua-discovery") hasta que el usuario lo edita a mano.
                     Interacción también estilo Odoo (rediseño 2026-09-05, pedido
                     explícito): mientras no se toca, se muestra como texto de solo
                     lectura ("preview") con un ícono de editar — no como un input
                     editable desde el arranque. `$subdominioTouched` hace doble
                     trabajo acá: además de apagar el auto-slug (ver
                     updatedNombreEmpresa()), decide qué de las dos vistas se
                     renderiza. Clickear "editar" lo marca touched directamente
                     (equivale a que el usuario haya tocado el campo a mano). --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600 block">
                        Subdominio <span class="text-state-error">*</span>
                    </label>

                    @if (! $subdominioTouched)
                        @php $unavailableReason = $this->subdomainUnavailableReason; @endphp
                        <div @class([
                            'flex items-center gap-2.5 rounded-lg border pl-3.5 pr-2 py-2.5',
                            'border-state-error bg-state-error/5' => $unavailableReason,
                            'border-slate-200 bg-slate-50' => ! $unavailableReason,
                        ])>
                            <x-heroicon-s-globe-alt @class(['w-5 h-5 flex-shrink-0', 'text-state-error' => $unavailableReason, 'text-slate-400' => ! $unavailableReason]) />
                            <span @class(['text-sm font-medium truncate', 'text-state-error' => $unavailableReason, 'text-slate-700' => ! $unavailableReason])>
                                {{ $subdominio !== '' ? $subdominio : 'tu-negocio' }}.{{ request()->getHost() }}
                            </span>
                            <button
                                type="button"
                                wire:click="$set('subdominioTouched', true)"
                                class="ml-auto flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center text-slate-400 hover:text-zertix-primary hover:bg-white transition-colors"
                                aria-label="Editar subdominio"
                            >
                                <x-heroicon-s-pencil class="w-4 h-4" />
                            </button>
                        </div>
                        {{-- Mismo estilo de error que el resto del formulario (texto rojo,
                             sin ícono aparte) — prioriza el error real del servidor
                             (post-submit) sobre el aviso en vivo (pre-submit). --}}
                        @if ($errors->has('subdominio'))
                            <p class="text-xs font-medium text-state-error break-words">{{ $errors->first('subdominio') }}</p>
                        @elseif ($unavailableReason)
                            <p class="text-xs font-medium text-state-error break-words">{{ $unavailableReason }}</p>
                        @endif
                    @else
                        <x-ui.forms.input
                            name="subdominio"
                            wire:model.live.debounce.500ms="subdominio"
                            placeholder="tu-negocio"
                            icon-left="heroicon-s-globe-alt"
                            :addonRight="'.' . request()->getHost()"
                            :error="$errors->first('subdominio')"
                            required
                        />
                    @endif
                </div>
            </div>
        </div>

        <div class="h-px bg-gray-100"></div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Tipo de documento explícito — antes se inferia del largo del numero
                 (9 digitos = RNC, 11 = Cedula) y ese resultado nunca se guardaba
                 realmente en tax_identifier_type. Ahora es un campo propio, en el
                 orden pedido: nombre, tipo, numero. --}}
            <x-ui.forms.select
                label="Tipo de Documento"
                name="taxIdentifierType"
                wire:model="taxIdentifierType"
                placeholder="Seleccione un tipo"
                :error="$errors->first('taxIdentifierType')"
                required
            >
                @foreach (\App\Enums\TaxIdentifierType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </x-ui.forms.select>

            <x-ui.forms.input
                label="Número de Documento"
                name="taxId"
                wire:model="taxId"
                placeholder="131-XXXXX-X"
                :error="$errors->first('taxId')"
                required
            />

            {{-- Provincia/Municipio — listas estáticas (InstallWizard::provinceOptions()/
                 municipalityOptions()), no una consulta a `provinces`/`municipalities`:
                 en este paso el Tenant todavía no existe, no hay ninguna base de tenant
                 contra la cual consultar. Mismo cascadeo 100% client-side (Alpine +
                 @entangle) que ya usaba el wizard viejo con datos reales — acá la
                 fuente es el array estático en vez de Municipality::all(). x-ui.forms.select
                 reenvía cualquier atributo extra (x-model, @change) al <select> nativo. --}}
            <div x-data="{
                    municipalities: {{ \Illuminate\Support\Js::from($this->municipalityOptions()) }},
                    provinciaId: @entangle('provinciaId'),
                    municipioId: @entangle('municipioId'),
                    get filtered() { return Object.entries(this.municipalities).filter(([id, m]) => m.province_id == this.provinciaId); },
                }" class="contents">
                <x-ui.forms.select
                    label="Provincia"
                    name="provinciaId"
                    x-model="provinciaId"
                    @change="municipioId = null"
                    placeholder="Seleccione una provincia"
                    :error="$errors->first('provinciaId')"
                    required
                >
                    @foreach ($this->provinceOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.select
                    label="Municipio"
                    name="municipioId"
                    x-model="municipioId"
                    x-bind:disabled="!provinciaId"
                    placeholder="Seleccione un municipio"
                    :error="$errors->first('municipioId')"
                >
                    <template x-for="[id, m] in filtered" :key="id">
                        <option :value="id" x-text="m.name"></option>
                    </template>
                </x-ui.forms.select>
            </div>

            <div class="sm:col-span-2">
                <x-ui.forms.input
                    label="Dirección Física"
                    name="direccion"
                    wire:model="direccion"
                    placeholder="Calle, Número, Sector"
                    :error="$errors->first('direccion')"
                    required
                />
            </div>
        </div>

        {{-- Mobile: 2 columnas parejas (antes se apretaban en una sola fila) — desktop
             vuelve al layout natural, Atrás como link chico a la izquierda. --}}
        <div class="grid grid-cols-2 gap-3 pt-2 sm:flex sm:items-center sm:justify-between">
            <x-ui.button type="button" variant="secondary" appearance="ghost" wire:click="prevStep" iconLeft="heroicon-s-arrow-left">
                Atrás
            </x-ui.button>
            {{-- Solo valida y avanza — todavía no se crea nada (ver InstallWizard,
                 el Tenant nace recién en "Finalizar"). El guard de doble-submit de
                 x-ui.button (automático en type="submit") evita un segundo click
                 mientras la validación todavía procesa. --}}
            <x-ui.button type="submit" variant="primary" iconRight="heroicon-s-arrow-right">
                Siguiente: Elegir Tipo de Negocio
            </x-ui.button>
        </div>
    </form>
</div>
