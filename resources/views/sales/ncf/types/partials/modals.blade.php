{{-- MODAL CREAR TIPO DE NCF --}}
<x-modal name="create-ncf-type" maxWidth="md">
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Nuevo Tipo de Comprobante</h2>
        <p class="mt-1 text-sm text-gray-600">Defina las reglas para un nuevo tipo de NCF o e-NCF.</p>

        <form action="{{ route('finance.ncf.types.store') }}" 
              method="POST" 
              class="mt-6 space-y-4"
              x-data="{ 
                isElectronic: false,
                get prefix() { return this.isElectronic ? 'E' : 'B'; }
              }">
            @csrf
            
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <x-ui.forms.input label="Nombre del Comprobante" name="name" id="name" type="text"
                        placeholder="Ej: Factura de Crédito Fiscal" required :error="$errors->first('name')" />
                </div>
                <div>
                    <x-ui.forms.input label="Código" name="code" id="code" type="text"
                        class="text-center font-mono" placeholder="01" maxlength="2" required
                        :error="$errors->first('code')" hint="Código DGII de 2 dígitos (ej. 01, 02, 14)" />
                </div>
            </div>

            {{-- Configuración Técnica con Checkboxes Estilizados --}}
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-4">
                <x-ui.forms.checkbox label="¿Es Electrónico? (e-NCF)" name="is_electronic" id="is_electronic"
                    value="1" x-model="isElectronic" description="Determina si usa el prefijo 'E'" />

                <div class="pt-2 border-t border-gray-200">
                    <x-ui.forms.checkbox label="Requiere RNC Obligatorio" name="requires_rnc" id="requires_rnc"
                        value="1" description="Valida RNC del cliente en el POS" />
                </div>
            </div>

            <div>
                <x-input-label value="Prefijo Asignado" />
                <div class="mt-1 flex items-center">
                    <span class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-gray-100 text-gray-600 font-mono font-bold text-lg" x-text="prefix"></span>
                    <input type="hidden" name="prefix" :value="prefix">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Guardar Tipo</x-ui.button>
            </div>
        </form>
    </div>
</x-modal>

{{-- MODALES DE EDICIÓN --}}
@foreach($items as $type)
    <x-modal name="edit-ncf-type-{{ $type->id }}" maxWidth="md">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900">Configurar Comprobante</h2>
                <span class="px-2 py-1 text-xs font-mono bg-gray-100 text-gray-600 rounded border border-gray-200">
                    ID: {{ $type->id }}
                </span>
            </div>
            
            <form action="{{ route('finance.ncf.types.update', $type->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                
                {{-- Nombre --}}
                <div>
                    <x-ui.forms.input label="Nombre del Comprobante" name="name" id="edit_name_{{ $type->id }}"
                        type="text" value="{{ $type->name }}" required :error="$errors->first('name')" />
                </div>

                {{-- Visualización de Identificadores (No editables por seguridad fiscal) --}}
                <div class="grid grid-cols-2 gap-4 bg-indigo-50 p-4 rounded-xl border border-indigo-100 shadow-sm text-center">
                    <div>
                        <span class="text-[10px] text-indigo-400 uppercase font-black tracking-widest block">Código DGII</span>
                        <span class="font-mono text-indigo-700 font-bold text-2xl">{{ $type->code }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-indigo-400 uppercase font-black tracking-widest block">Prefijo Serie</span>
                        <span class="font-mono text-indigo-700 font-bold text-2xl">{{ $type->prefix }}</span>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- Toggle: RNC --}}
                    <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <x-ui.forms.checkbox label="Validación de RNC" name="requires_rnc"
                            id="edit_rnc_{{ $type->id }}" value="1" :checked="$type->requires_rnc"
                            description="Hacer obligatorio para el POS" />
                    </div>

                    {{-- Toggle: Estado Activo --}}
                    <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <x-ui.forms.checkbox label="Disponibilidad" name="is_active"
                            id="edit_active_{{ $type->id }}" value="1" :checked="$type->is_active"
                            description="Permitir uso en facturación" />
                    </div>

                    {{-- Alerta de Seguridad (Si tiene secuencias) --}}
                    @if($type->sequences_count > 0)
                        <div class="p-4 bg-amber-50 border-l-4 border-amber-400 rounded-r-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-bold text-amber-800 uppercase tracking-tight">Registro con Dependencias</h3>
                                    <div class="mt-1 text-xs text-amber-700">
                                        <p>Este tipo tiene <strong>{{ $type->sequences_count }} secuencias</strong> vinculadas. Desactivarlo impedirá la emisión de nuevos comprobantes de este tipo aunque el lote tenga disponibilidad.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Descartar</x-ui.button>
                    <x-ui.button type="submit" variant="primary" class="shadow-md">
                        Guardar Cambios
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-modal>
@endforeach