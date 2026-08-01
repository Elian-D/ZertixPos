{{-- Envolvemos todo el formulario en Alpine para manejar la lógica de estados --}}
<div class="p-4 sm:p-8 grid grid-cols-1 lg:grid-cols-2 gap-6"
     x-data="{
        requiresPin: {{ old('requires_pin', $posTerminal->requires_pin ?? true) ? 'true' : 'false' }},
        isEdit: {{ isset($posTerminal) ? 'true' : 'false' }},
        hasExistingPin: {{ isset($posTerminal) && $posTerminal->access_pin ? 'true' : 'false' }},
        allowItemDiscount: {{ old('allow_item_discount', $posTerminal->allow_item_discount ?? true) ? 'true' : 'false' }},
        allowGlobalDiscount: {{ old('allow_global_discount', $posTerminal->allow_global_discount ?? true) ? 'true' : 'false' }}
     }">

    {{-- Sección 1: Identificación y Ubicación (Sin cambios significativos) --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
            <div class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Configuración General</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <x-input-label value="Nombre de la Terminal" />
                <x-text-input name="name" class="w-full mt-1" :value="old('name', $posTerminal->name ?? '')" placeholder="Ej: Caja Principal 01" required />
            </div>

            <div>
                <x-input-label value="Almacén de Despacho" />
                <select name="warehouse_id" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm" required>
                    <option value="">Seleccione almacén...</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $posTerminal->warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[10px] text-gray-400 italic">El inventario se descontará de este almacén.</p>
            </div>

            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <x-input-label value="¿Es Terminal Móvil?" class="mb-0" />
                <input type="hidden" name="is_mobile" value="0">
                <input type="checkbox" name="is_mobile" value="1" {{ old('is_mobile', $posTerminal->is_mobile ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer">
            </div>
        </div>
    </section>

    {{-- Sección 2: Finanzas y Facturación (CORREGIDA) --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
            <div class="w-7 h-7 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold text-xs">2</div>
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Finanzas y Operación</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            @if(general_config()?->esModoFiscal())
                <div class="md:col-span-2">
                    <x-input-label value="Tipo de Comprobante (NCF) por Defecto" />
                    <select name="default_ncf_type_id" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                        <option value="">Seleccione tipo...</option>
                        @foreach($ncf_types as $ncf)
                            <option value="{{ $ncf['id'] }}" {{ old('default_ncf_type_id', $posTerminal->default_ncf_type_id ?? '') == $ncf['id'] ? 'selected' : '' }}>
                                {{ $ncf['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="md:col-span-2">
                <x-input-label value="Cliente por Defecto (Walk-in)" />
                <select name="default_client_id" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                    <option value="" {{ is_null(old('default_client_id', $posTerminal->default_client_id ?? null)) ? 'selected' : '' }}>
                        Heredar de Ajustes POS (Actual: {{ $global_client_name }})
                    </option>
                    @foreach($clients as $client)
                        <option value="{{ $client['id'] }}" {{ old('default_client_id', $posTerminal->default_client_id ?? '') == $client['id'] ? 'selected' : '' }}>
                            {{ $client['name'] }} @if($client['tax_id'] != 'N/A') — {{ $client['tax_id'] }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- Sección 3: Hardware (Sin cambios) --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
            <div class="w-7 h-7 bg-amber-500 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Hardware y Operación</h3>
        </div>

        <div class="space-y-6">
            <div>
                <x-input-label value="Formato de Impresión" />
                {{-- Toggle en vez de <select> — con solo 3 opciones un desplegable es fricción
                     de más; mismo patrón visual (radio + peer-checked) que ya usa el tamaño de
                     papel en Configuración General del POS, sin necesitar Alpine nuevo. --}}
                <div class="grid grid-cols-3 gap-3 mt-1">
                    <label class="cursor-pointer group">
                        <input type="radio" name="printer_format" value="" class="sr-only peer" {{ is_null(old('printer_format', $posTerminal->printer_format ?? null)) ? 'checked' : '' }}>
                        <div class="h-full flex flex-col justify-center p-4 sm:p-5 border-2 border-gray-100 rounded-xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-center">
                            <p class="text-sm sm:text-base font-bold text-gray-700 group-hover:text-indigo-600">Heredar</p>
                            <p class="text-[10px] sm:text-xs text-gray-400 italic mt-1 truncate">{{ $global_printer_format }}</p>
                        </div>
                    </label>
                    @foreach($printer_formats as $format)
                        <label class="cursor-pointer group">
                            <input type="radio" name="printer_format" value="{{ $format['id'] }}" class="sr-only peer" {{ old('printer_format', $posTerminal->printer_format ?? '') == $format['id'] ? 'checked' : '' }}>
                            <div class="h-full flex flex-col justify-center p-4 sm:p-5 border-2 border-gray-100 rounded-xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-center">
                                <p class="text-sm sm:text-base font-bold text-gray-700 group-hover:text-indigo-600">{{ $format['id'] }}</p>
                                <p class="text-[10px] sm:text-xs text-gray-400 italic mt-1 truncate">{{ str_contains($format['name'], 'Estándar') ? 'Estándar' : 'Portátil' }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <x-input-label value="Estado Operativo" class="mb-0" />
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $posTerminal->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 cursor-pointer">
            </div>
        </div>
    </section>

    {{-- Sección 4: Política de Descuentos (100% por terminal, sin fallback global, topes
         separados por ítem y global — ver 11.2/11.2.5 en POS-Interfaz.md) --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
            <div class="w-7 h-7 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold text-xs">4</div>
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Política de Descuentos</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <x-input-label value="¿Permite Descuento por Ítem?" class="mb-0" />
                    <input type="hidden" name="allow_item_discount" value="0">
                    <input type="checkbox" name="allow_item_discount" value="1" x-model="allowItemDiscount" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 cursor-pointer">
                </div>

                <div x-show="allowItemDiscount" x-transition>
                    <x-input-label value="Límite de Descuento por Ítem (%)" />
                    <div class="relative mt-1">
                        <x-text-input type="number" step="0.01" min="0" max="100" name="max_item_discount_percentage" class="w-full pr-8" :value="old('max_item_discount_percentage', $posTerminal->max_item_discount_percentage ?? 5.00)" ::required="allowItemDiscount" />
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 text-sm">%</span>
                    </div>
                    <p class="mt-1 text-[10px] text-gray-400 italic">Tope por línea de producto que el cajero puede aplicar en el carrito.</p>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <x-input-label value="¿Permite Descuento Global?" class="mb-0" />
                    <input type="hidden" name="allow_global_discount" value="0">
                    <input type="checkbox" name="allow_global_discount" value="1" x-model="allowGlobalDiscount" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 cursor-pointer">
                </div>

                <div x-show="allowGlobalDiscount" x-transition>
                    <x-input-label value="Límite de Descuento Global (%)" />
                    <div class="relative mt-1">
                        <x-text-input type="number" step="0.01" min="0" max="100" name="max_global_discount_percentage" class="w-full pr-8" :value="old('max_global_discount_percentage', $posTerminal->max_global_discount_percentage ?? 10.00)" ::required="allowGlobalDiscount" />
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 text-sm">%</span>
                    </div>
                    <p class="mt-1 text-[10px] text-gray-400 italic">Tope al total de la factura. Bajo la Regla de Exclusión, solo aplica a productos sin descuento propio.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Sección 5: Seguridad Operativa (CORREGIDA CON ALPINE) — ocupa el ancho completo del
         grid porque ya tiene su propio layout interno a 2 columnas. --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6 lg:col-span-2">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
            <div class="w-7 h-7 bg-slate-800 text-white rounded-full flex items-center justify-center font-bold text-xs">5</div>
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Seguridad y Acceso</h3>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <input type="hidden" name="requires_pin" value="0">
                    <input type="checkbox" name="requires_pin" value="1" 
                        x-model="requiresPin"
                        class="mt-1 rounded border-gray-300 text-slate-800 focus:ring-slate-800 w-5 h-5 cursor-pointer flex-shrink-0">
                    <div class="flex-1">
                        <x-input-label value="¿Habilitar PIN de Seguridad?" class="mb-1 font-bold text-slate-700" />
                        <p class="text-xs text-gray-500 leading-relaxed italic">
                            Si se desactiva, cualquier usuario con permisos podrá abrir la terminal sin código adicional.
                        </p>
                    </div>
                </div>

                <div x-show="requiresPin" x-transition class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex gap-3 items-start">
                    <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <p class="text-[11px] text-indigo-700 leading-tight">
                        <strong>Entrada Rápida:</strong> Diseñado para pantallas táctiles. Use 4 dígitos numéricos.
                    </p>
                </div>
            </div>

            <div class="flex flex-col justify-center" x-show="requiresPin" x-transition>
                <div class="bg-slate-50 rounded-2xl p-6 border-2 border-slate-200" 
                     :class="requiresPin ? 'opacity-100' : 'opacity-50 pointer-events-none'">
                    
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
                            class="w-full max-w-[240px] text-center text-4xl sm:text-5xl tracking-[0.5em] font-mono font-bold bg-white border-3 border-slate-300 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 rounded-xl py-4 sm:py-5 shadow-sm"
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
        </div>
    </section>
</div>