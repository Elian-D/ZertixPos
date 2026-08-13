{{-- MODAL CREAR --}}
    <x-modal name="crear-warehouse" maxWidth="md">

        <x-form-header
            title="Nuevo Almacén"
            subtitle="Registre una nueva ubicación de inventario (fija o móvil)."
            :back-route="route('inventory.warehouses.index')" />

        <form action="{{ route('inventory.warehouses.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-4">
                {{-- Nombre --}}
                <div>
                    <x-input-label for="name" value="Nombre del almacén" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Ej: Bodega Central o Camión #01" required />
                </div>

                {{-- Tipo de Almacén --}}
                <div>
                    <x-input-label for="type" value="Tipo de ubicación" />
                    <select name="type" id="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dirección/Ubicación --}}
                <div>
                    <x-input-label for="address" value="Dirección o Referencia" />
                    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" placeholder="Dirección física o placa del vehículo" />
                </div>

                {{-- Descripción --}}
                <div>
                    <x-input-label for="description" value="Descripción (Opcional)" />
                    <textarea name="description" id="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description') }}</textarea>
                </div>

                {{-- Estado Operativo (Mantenemos tu diseño de radios) --}}
                <div>
                    <x-input-label value="Estado Operativo" />
                    <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="1" class="peer hidden" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm">
                                Activo
                            </span>
                        </label>
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="0" class="peer hidden" {{ old('is_active') == '0' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-sm">
                                Inactivo
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-green-600">Guardar Almacén</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    @foreach($warehouses as $item)

    <x-modal name="view-warehouse-{{ $item->id }}" maxWidth="2xl">
    <div class="overflow-hidden rounded-xl bg-white shadow-2xl">
        {{-- Header con Identidad Visual --}}
        <div class="bg-gray-50 px-8 py-6 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-black text-gray-900 tracking-tight">Expediente de Almacén</h3>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                        {{ $item->code ?? 'SIN CÓDIGO' }}
                    </span>
                    <span class="text-gray-300 text-xs">•</span>
                    <span class="text-xs text-gray-500 italic uppercase tracking-tighter">ID Sistema: {{ $item->id }}</span>
                </div>
            </div>

            {{-- Badge de Estado --}}
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset shadow-sm {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-red-50 text-red-700 ring-red-600/20' }}">
                <span class="w-1.5 h-1.5 rounded-full mr-2 bg-current {{ $item->is_active ? 'animate-pulse' : '' }}"></span>
                {{ $item->is_active ? 'OPERATIVO' : 'INACTIVO' }}
            </span>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                {{-- Columna Izquierda: Información General --}}
                <div class="space-y-6">
                    <div class="flex gap-3">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 shrink-0">
                            <x-heroicon-s-building-office-2 class="w-5 h-5"/>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Nombre Comercial</span>
                            <p class="text-sm font-bold text-gray-800">{{ $item->name }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 shrink-0">
                            <x-heroicon-s-tag class="w-5 h-5"/>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Clasificación</span>
                            <p class="text-sm font-semibold text-gray-700">{{ $item->type_label }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center text-amber-600 shrink-0">
                            <x-heroicon-s-map-pin class="w-5 h-5"/>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Dirección Física</span>
                            <p class="text-sm font-semibold text-gray-700 leading-tight">
                                {{ $item->address ?? 'No especificada' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Columna Derecha: Configuración Contable --}}
                <div class="space-y-6">
                    {{-- Card Informativa de Cuenta Contable — solo con accounting.advanced activo
                         (REQ-02.12), un almacén no depende de Contabilidad para nada más. --}}
                    @if (module_enabled('accounting.advanced'))
                        <div class="p-5 bg-slate-900 rounded-2xl shadow-lg relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl"></div>

                            <span class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest block mb-4">Enlace Contable (Kardex)</span>

                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <x-heroicon-s-book-open class="w-5 h-5 text-white"/>
                                </div>
                                <div>
                                    @if($item->accountingAccount)
                                        <p class="text-sm font-mono font-bold text-white tracking-wider">
                                            {{ $item->accountingAccount->code }}
                                        </p>
                                        <p class="text-[11px] text-indigo-200 font-medium leading-tight mt-1">
                                            {{ $item->accountingAccount->name }}
                                        </p>
                                    @else
                                        <p class="text-xs text-amber-400 italic">Sin cuenta vinculada</p>
                                    @endif
                                </div>
                            </div>

                            <hr class="border-white/10 my-4">

                            <div class="flex justify-between items-center">
                                <span class="text-[9px] text-white/50 uppercase font-bold">Uso de Cuenta:</span>
                                <span class="px-2 py-0.5 bg-indigo-500/20 text-[9px] text-indigo-200 rounded border border-indigo-500/30 font-black">ACTIVO CIRCULANTE</span>
                            </div>
                        </div>
                    @endif

                    {{-- Indicador de Stock --}}
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase block">Productos Vinculados</span>
                            <p class="text-lg font-black text-gray-800">{{ $item->stocks_count ?? $item->stocks()->count() }}</p>
                        </div>
                        <x-heroicon-s-archive-box class="w-8 h-8 text-gray-200" />
                    </div>
                </div>

                {{-- Descripción / Notas --}}
                <div class="col-span-1 md:col-span-2">
                    <div class="bg-gray-50 p-4 rounded-xl border border-dashed border-gray-200">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Notas Administrativas</span>
                        <p class="text-sm text-gray-700 leading-relaxed italic">
                            "{{ $item->description ?? 'Sin observaciones adicionales para este almacén.' }}"
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer Info --}}
            <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between text-[11px] text-gray-400 uppercase font-bold tracking-tighter">
                <span>Alta: {{ $item->created_at->format('d/m/Y H:i') }}</span>
                <span>Sincronizado: {{ $item->updated_at->diffForHumans() }}</span>
            </div>
        </div>

        {{-- Acciones del Modal --}}
        <div class="px-8 py-5 bg-gray-50 border-t flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
            <button @click="$dispatch('close'); $dispatch('open-modal', 'edit-warehouse-{{ $item->id }}')" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                <x-heroicon-s-pencil class="w-3 h-3 mr-2"/> Modificar Datos
            </button>
        </div>
    </div>
</x-modal>
    <x-modal name="edit-warehouse-{{ $item->id }}" maxWidth="md">

        <x-form-header
            title="Editar Almacén: {{ $item->name }}"
            subtitle="Modifique la información de la ubicación."
            :back-route="route('inventory.warehouses.index')" />

        <form method="POST" action="{{ route('inventory.warehouses.update', $item) }}" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-input-label value="Nombre del almacén" />
                    <x-text-input name="name" type="text" class="mt-1 block w-full" value="{{ $item->name }}" required />
                </div>

                <div>
                    <x-input-label value="Tipo de ubicación" />
                    <select name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" {{ (old('type', $item->type) == $value) ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label value="Dirección o Referencia" />
                    <x-text-input name="address" type="text" class="mt-1 block w-full" value="{{ $item->address }}" />
                </div>

                <div>
                    <x-input-label value="Descripción" />
                    <textarea name="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description', $item->description) }}</textarea>
                </div>

                <div>
                    <x-input-label value="Estado Operativo" />
                    <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="1" class="peer hidden" {{ old('is_active', $item->is_active) == '1' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm">
                                Activo
                            </span>
                        </label>
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="0" class="peer hidden" {{ old('is_active', $item->is_active) == '0' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-sm">
                                Inactivo
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-green-600">Actualizar Almacén</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-ui.confirm-deletion-modal 
        :id="$item->id"
        :title="'¿Eliminar Almacén?'"
        :itemName="$item->name"
        :type="'el almacén'"
        :route="route('inventory.warehouses.destroy', $item)"
    />
    @endforeach