{{-- Envolvemos todo el formulario en Alpine para manejar la lógica de estados. Las
     secciones flotan directamente sobre el fondo gris de la página (sin una tarjeta
     blanca grande envolviendo todo) — cada <section> ya es su propia tarjeta, con un
     ícono junto al título (sin círculo numerado — diseño de referencia en Stitch).
     Layout de 3 columnas (2/3 + 1/3) en desktop para no volverse un scroll infinito
     de una sola columna: izquierda = Configuración General y Funcionalidades (las
     secciones grandes), derecha = Finanzas, Hardware y Descuentos apiladas. --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="{
        requiresPin: {{ old('requires_pin', $posTerminal->requires_pin ?? true) ? 'true' : 'false' }},
        isEdit: {{ isset($posTerminal) ? 'true' : 'false' }},
        hasExistingPin: {{ isset($posTerminal) && $posTerminal->access_pin ? 'true' : 'false' }},
        isMobile: {{ old('is_mobile', $posTerminal->is_mobile ?? false) ? 'true' : 'false' }},
        isActive: {{ old('is_active', $posTerminal->is_active ?? true) ? 'true' : 'false' }},
        allowReceivableCollection: {{ old('allow_receivable_collection', $posTerminal->allow_receivable_collection ?? true) ? 'true' : 'false' }},
        allowItemDiscount: {{ old('allow_item_discount', $posTerminal->allow_item_discount ?? true) ? 'true' : 'false' }},
        allowGlobalDiscount: {{ old('allow_global_discount', $posTerminal->allow_global_discount ?? true) ? 'true' : 'false' }}
     }">

    {{-- Columna izquierda (2/3): secciones grandes --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Configuración General --}}
        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
                <x-heroicon-s-cog-6-tooth class="w-5 h-5 text-zertix-secondary" />
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Configuración General</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-ui.forms.input label="Nombre de la Terminal" name="name" value="{{ old('name', $posTerminal->name ?? '') }}" placeholder="Ej: Caja Principal 01" required :error="$errors->first('name')" />
                </div>

                <div class="md:col-span-2">
                    <x-ui.forms.select label="Almacén de Despacho" name="warehouse_id" placeholder="Seleccione almacén..." required :error="$errors->first('warehouse_id')">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $posTerminal->warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </x-ui.forms.select>
                    <p class="mt-1 text-[10px] text-gray-400 italic">El inventario se descontará de este almacén.</p>
                </div>
            </div>
        </section>

        {{-- Funcionalidades de la Terminal — grilla 2x2 de toggles reales. Cobro de CxC
             (Fase 6, REQ-6.5) se agrega acá, mismo patrón visual que Móvil/Activa/PIN. --}}
        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
                <x-heroicon-s-adjustments-horizontal class="w-5 h-5 text-zertix-secondary" />
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Funcionalidades de la Terminal</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Terminal Móvil — @click="isMobile = !isMobile" en la tarjeta completa
                     conserva el "clic en cualquier parte de la tarjeta" que daba el <label>
                     original; el wrapper interno con @click.stop evita que el propio click
                     del switch (que ya hace su propio toggle) dispare un segundo toggle por
                     burbujeo, dejando la tarjeta en el estado contrario al que se ve. --}}
                <div class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                     @click="isMobile = !isMobile"
                     :class="isMobile ? 'border-zertix-primary bg-zertix-primary/5' : 'border-gray-100 hover:border-gray-200'">
                    <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                         :class="isMobile ? 'bg-zertix-primary text-white' : 'bg-gray-100 text-gray-400'">
                        <x-heroicon-s-device-phone-mobile class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800">Terminal Móvil</p>
                        <p class="text-xs text-gray-500 mt-0.5">Terminal portátil, ej. tablet en ruta.</p>
                    </div>
                    <input type="hidden" name="is_mobile" value="0">
                    <div class="mt-0.5" @click.stop>
                        <x-ui.forms.toggle name="is_mobile" value="1" x-model="isMobile" />
                    </div>
                </div>

                {{-- Terminal Activa --}}
                <div class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                     @click="isActive = !isActive"
                     :class="isActive ? 'border-zertix-primary bg-zertix-primary/5' : 'border-gray-100 hover:border-gray-200'">
                    <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                         :class="isActive ? 'bg-zertix-primary text-white' : 'bg-gray-100 text-gray-400'">
                        <x-heroicon-s-power class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800">Terminal Activa</p>
                        <p class="text-xs text-gray-500 mt-0.5">Disponible para abrir turno. Inactiva no aparece en el Lobby.</p>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <div class="mt-0.5" @click.stop>
                        <x-ui.forms.toggle name="is_active" value="1" x-model="isActive" />
                    </div>
                </div>

                {{-- Requiere PIN de Seguridad --}}
                <div class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                     @click="requiresPin = !requiresPin"
                     :class="requiresPin ? 'border-zertix-primary bg-zertix-primary/5' : 'border-gray-100 hover:border-gray-200'">
                    <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                         :class="requiresPin ? 'bg-zertix-primary text-white' : 'bg-gray-100 text-gray-400'">
                        <x-heroicon-s-lock-closed class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800">Requiere PIN de Seguridad</p>
                        <p class="text-xs text-gray-500 mt-0.5">Exige código de 4 dígitos para operar esta terminal.</p>
                    </div>
                    <input type="hidden" name="requires_pin" value="0">
                    <div class="mt-0.5" @click.stop>
                        <x-ui.forms.toggle name="requires_pin" value="1" x-model="requiresPin" />
                    </div>
                </div>

                {{-- Permite Cobro de Cuentas por Cobrar (Fase 6, REQ-6.5) --}}
                <div class="relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                     @click="allowReceivableCollection = !allowReceivableCollection"
                     :class="allowReceivableCollection ? 'border-zertix-primary bg-zertix-primary/5' : 'border-gray-100 hover:border-gray-200'">
                    <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                         :class="allowReceivableCollection ? 'bg-zertix-primary text-white' : 'bg-gray-100 text-gray-400'">
                        <x-heroicon-s-currency-dollar class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800">Permite Cobro de Cuentas por Cobrar</p>
                        <p class="text-xs text-gray-500 mt-0.5">Habilita el botón "Cobrar Deudas" en el punto de venta.</p>
                    </div>
                    <input type="hidden" name="allow_receivable_collection" value="0">
                    <div class="mt-0.5" @click.stop>
                        <x-ui.forms.toggle name="allow_receivable_collection" value="1" x-model="allowReceivableCollection" />
                    </div>
                </div>
            </div>

            {{-- Panel de PIN: solo visible si "Requiere PIN de Seguridad" está activo — se
                 oculta por completo (no solo se deshabilita) al desactivar el toggle. --}}
            <div x-show="requiresPin" x-transition class="mt-4">
                <div class="bg-slate-50 rounded-2xl p-6 border-2 border-slate-200">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 text-center">
                        <span x-show="!hasExistingPin">Ingrese PIN de 4 dígitos</span>
                        <span x-show="hasExistingPin" x-cloak>Actualizar PIN (opcional)</span>
                    </label>

                    <div class="flex justify-center">
                        <x-text-input
                            name="access_pin"
                            type="text"
                            maxlength="4"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            class="w-full max-w-[240px] text-center text-4xl sm:text-5xl tracking-[0.5em] font-mono font-bold bg-white border-3 border-slate-300 focus:border-zertix-primary focus:ring-4 focus:ring-zertix-primary/20 rounded-xl py-4 sm:py-5 shadow-sm"
                            placeholder="••••"
                            ::required="requiresPin && !hasExistingPin"
                            ::disabled="!requiresPin"
                            autocomplete="new-password"
                            @input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                        />
                    </div>

                    <p x-show="hasExistingPin" x-cloak class="mt-2 text-[10px] text-slate-400 text-center italic">
                        Deja en blanco para mantener el PIN actual.
                    </p>
                </div>
            </div>
        </section>
    </div>

    {{-- Columna derecha (1/3): secciones cortas apiladas --}}
    <div class="space-y-6">

        {{-- Finanzas y Facturación --}}
        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
                <x-heroicon-s-building-library class="w-5 h-5 text-zertix-secondary" />
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Finanzas</h3>
            </div>

            <div class="space-y-6">
                @if(general_config()?->esModoFiscal())
                    <div>
                        <x-ui.forms.select label="NCF por Defecto" name="default_ncf_type_id" placeholder="Seleccione tipo..." :error="$errors->first('default_ncf_type_id')">
                            @foreach($ncf_types as $ncf)
                                <option value="{{ $ncf['id'] }}" {{ old('default_ncf_type_id', $posTerminal->default_ncf_type_id ?? '') == $ncf['id'] ? 'selected' : '' }}>
                                    {{ $ncf['name'] }}
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>
                @endif

                <div>
                    <x-ui.forms.select label="Cliente por Defecto" name="default_client_id" placeholder="" :error="$errors->first('default_client_id')">
                        <option value="" {{ is_null(old('default_client_id', $posTerminal->default_client_id ?? null)) ? 'selected' : '' }}>
                            Heredar de Ajustes POS (Actual: {{ $global_client_name }})
                        </option>
                        @foreach($clients as $client)
                            <option value="{{ $client['id'] }}" {{ old('default_client_id', $posTerminal->default_client_id ?? '') == $client['id'] ? 'selected' : '' }}>
                                {{ $client['name'] }} @if($client['tax_id'] != 'N/A') — {{ $client['tax_id'] }} @endif
                            </option>
                        @endforeach
                    </x-ui.forms.select>
                </div>
            </div>
        </section>

        {{-- Hardware --}}
        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
                <x-heroicon-s-printer class="w-5 h-5 text-zertix-secondary" />
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Hardware</h3>
            </div>

            <div>
                <x-input-label value="Formato de Impresión" />
                <div class="grid grid-cols-3 gap-2 mt-1">
                    <label class="cursor-pointer group">
                        <input type="radio" name="printer_format" value="" class="sr-only peer" {{ is_null(old('printer_format', $posTerminal->printer_format ?? null)) ? 'checked' : '' }}>
                        <div class="h-full flex flex-col justify-center p-3 border-2 border-gray-100 rounded-xl peer-checked:border-zertix-primary peer-checked:bg-zertix-primary/5 transition-all text-center">
                            <p class="text-xs font-bold text-gray-700 group-hover:text-zertix-primary-dark">Heredar</p>
                            <p class="text-[9px] text-gray-400 italic mt-1 truncate">{{ $global_printer_format }}</p>
                        </div>
                    </label>
                    @foreach($printer_formats as $format)
                        <label class="cursor-pointer group">
                            <input type="radio" name="printer_format" value="{{ $format['id'] }}" class="sr-only peer" {{ old('printer_format', $posTerminal->printer_format ?? '') == $format['id'] ? 'checked' : '' }}>
                            <div class="h-full flex flex-col justify-center p-3 border-2 border-gray-100 rounded-xl peer-checked:border-zertix-primary peer-checked:bg-zertix-primary/5 transition-all text-center">
                                <p class="text-xs font-bold text-gray-700 group-hover:text-zertix-primary-dark">{{ $format['id'] }}</p>
                                <p class="text-[9px] text-gray-400 italic mt-1 truncate">{{ str_contains($format['name'], 'Estándar') ? 'Estándar' : 'Portátil' }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Política de Descuentos --}}
        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
                <x-heroicon-s-tag class="w-5 h-5 text-zertix-secondary" />
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Descuentos</h3>
            </div>

            <div class="space-y-5">
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <input type="hidden" name="allow_item_discount" value="0">
                        <x-ui.forms.toggle name="allow_item_discount" value="1" x-model="allowItemDiscount" label="Por Ítem" />
                        <div class="relative w-20">
                            <x-text-input type="number" step="0.01" min="0" max="100" name="max_item_discount_percentage" class="w-full text-right pr-6 py-1.5 text-sm focus:border-zertix-primary focus:ring-zertix-primary" :value="old('max_item_discount_percentage', $posTerminal->max_item_discount_percentage ?? 5.00)" ::disabled="!allowItemDiscount" ::required="allowItemDiscount" />
                            <span class="absolute inset-y-0 right-2 flex items-center text-gray-400 text-xs">%</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 italic">Tope por línea de producto en el carrito.</p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <input type="hidden" name="allow_global_discount" value="0">
                        <x-ui.forms.toggle name="allow_global_discount" value="1" x-model="allowGlobalDiscount" label="Global" />
                        <div class="relative w-20">
                            <x-text-input type="number" step="0.01" min="0" max="100" name="max_global_discount_percentage" class="w-full text-right pr-6 py-1.5 text-sm focus:border-zertix-primary focus:ring-zertix-primary" :value="old('max_global_discount_percentage', $posTerminal->max_global_discount_percentage ?? 10.00)" ::disabled="!allowGlobalDiscount" ::required="allowGlobalDiscount" />
                            <span class="absolute inset-y-0 right-2 flex items-center text-gray-400 text-xs">%</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 italic">Tope al total de la factura (Regla de Exclusión).</p>
                </div>
            </div>
        </section>
    </div>
</div>
