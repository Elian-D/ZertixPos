{{-- MODAL CREAR LOTE NCF --}}
<x-modal name="create-ncf-sequence" maxWidth="md">
    <x-form-header 
        title="Nuevo Lote de Comprobantes" 
        subtitle="Configure los rangos autorizados por la DGII." />

    <form action="{{ route('finance.ncf.sequences.store') }}" 
          method="POST" 
          class="p-6"
          x-data="{ 
            typeId: '',
            startNum: 1,
            endNum: '',
            prefixes: {{ json_encode($ncf_types_prefixes) }},
            codes: {{ json_encode($ncf_types_codes) }},
            // Agregamos un mapeo de cuáles son electrónicos
            electronics: {{ json_encode($ncf_types_electronic_status) }}, 
            
            get isElectronic() { return this.electronics[this.typeId] || false; },
            get currentPrefix() { return this.prefixes[this.typeId] || 'B'; },
            get typeCode() { return this.codes[this.typeId] || '01'; },
            
            // El padding cambia dinámicamente
            formatNcf(val) { 
                let pad = this.isElectronic ? 10 : 8;
                return val.toString().padStart(pad, '0'); 
            }
        }">
        @csrf
        
        <div class="space-y-4">
            {{-- Tipo de NCF --}}
            <div>
                <x-ui.forms.select label="Tipo de Comprobante" name="ncf_type_id" id="ncf_type_id" x-model="typeId"
                    required placeholder="Seleccione tipo..." :error="$errors->first('ncf_type_id')">
                    @foreach($ncf_types as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- Serie (Solo Lectura) --}}
                <div>
                    <x-input-label value="Serie (Automática)" />
                    <div class="mt-1 block w-full bg-gray-50 border border-gray-200 rounded-md py-2 text-center font-bold text-gray-600"
                         x-text="currentPrefix"></div>
                </div>
                {{-- Vencimiento (Default 31 Dic) --}}
                <div>
                    <x-ui.forms.input
                        type="date"
                        label="Vencimiento (Automático)"
                        name="expiry_date"
                        value="{{ now()->addYear()->endOfYear()->format('Y-m-d') }}"
                        icon-right="heroicon-s-lock-closed"
                        readonly
                        :error="$errors->first('expiry_date')"
                        hint="Vence el último día del año siguiente."
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-ui.forms.input type="number" label="Desde (Inicio)" name="from" x-model.number="startNum"
                        min="1" class="font-mono" required :error="$errors->first('from')" />
                </div>
                <div>
                    <x-ui.forms.input type="number" label="Hasta (Fin)" name="to" x-model.number="endNum"
                        @bind:min="startNum" class="font-mono" required :error="$errors->first('to')" />
                </div>
            </div>

            {{-- Alerta de Agotamiento --}}
            <div>
                <x-ui.forms.input type="number" label="Alerta de Agotamiento (Quedando:)" name="alert_threshold"
                    value="50" min="1" placeholder="Ej. 50" required :error="$errors->first('alert_threshold')"
                    hint="Se notificará cuando queden estos números disponibles." />
            </div>

            {{-- Preview --}}
            <div class="bg-zertix-primary-50 border border-zertix-primary-100 rounded-lg p-3">
                <span class="text-[10px] text-zertix-primary-400 uppercase font-bold block mb-1">Vista Previa del NCF:</span>
                <div class="flex items-baseline gap-1 font-mono text-lg font-bold text-zertix-primary-700">
                    <span x-text="currentPrefix" class="text-zertix-primary-400"></span>
                    <span x-text="typeCode"></span>
                    <span x-text="formatNcf(startNum)"></span>
                </div>
                <p class="text-[10px] text-zertix-primary-400 mt-1" x-show="isElectronic">
                    * Estructura e-NCF detectada (10 dígitos de secuencia).
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
            <x-ui.button type="submit" variant="primary">Guardar Secuencia</x-ui.button>
        </div>
    </form>
</x-modal>

{{-- MODAL VER DETALLE / LOGS RÁPIDOS (OPCIONAL) --}}
@foreach($items as $item)

{{-- MODAL AMPLIAR RANGO NCF --}}

    <x-modal name="extend-sequence-{{ $item->id }}" maxWidth="sm">
        <x-form-header 
            title="Ampliar Rango" 
            subtitle="{{ $item->type->name }} ({{ $item->series }})" />

        <form action="{{ route('finance.ncf.sequences.extend', $item->id) }}" method="POST" class="p-6">
            @csrf
            @method('PATCH')
            
            <div class="space-y-4">
                <div class="bg-gray-50 p-3 rounded-lg border border-dashed border-gray-300">
                    <p class="text-xs text-gray-500 uppercase font-bold">Límite actual:</p>
                    <p class="text-lg font-mono font-bold text-gray-700">
                        {{ str_pad($item->to, $item->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}
                    </p>
                </div>

                <div>
                    <x-ui.forms.input
                        type="number"
                        label="Nuevo Límite (Hasta)"
                        name="new_to"
                        id="new_to"
                        value="{{ $item->to + 100 }}"
                        min="{{ $item->to + 1 }}"
                        required
                        class="font-mono text-lg"
                        :error="$errors->first('new_to')"
                        hint="Debe ser mayor al límite actual."
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Confirmar Ampliación
                </x-ui.button>
            </div>
        </form>
    </x-modal>

{{-- MODAL: UMBRAL DE ALERTA — antes vivía dentro del modal de detalle, que se
     reemplazó por el show de la secuencia (v1.4.0 REQ-3.14). --}}
<x-modal name="threshold-sequence-{{ $item->id }}" maxWidth="sm">
    <form action="{{ route('finance.ncf.sequences.update-threshold', $item->id) }}" method="POST" class="p-6">
        @csrf
        @method('PATCH')
        <h2 class="text-lg font-semibold text-gray-900">Umbral de alerta</h2>
        <p class="mt-1 text-sm text-gray-500">Se avisa que la secuencia se está agotando cuando queden esta cantidad de NCF o menos.</p>
        <div class="mt-4">
            <x-ui.forms.input type="number" min="0" label="Avisar cuando queden" name="alert_threshold"
                value="{{ $item->alert_threshold }}" required />
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
            <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Guardar</x-ui.button>
        </div>
    </form>
</x-modal>

{{-- MODAL: CONFIRMACIÓN DE ELIMINACIÓN — solo alcanzable si el lote sigue
     virgen (current < from), mismo guard que NcfSequenceService::delete(). --}}
@if($item->current < $item->from)
    <x-modal name="confirm-sequence-deletion-{{ $item->id }}" focusable>
        <form method="post" action="{{ route('finance.ncf.sequences.destroy', $item) }}" class="p-6 text-left">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-medium text-gray-900">
                ¿Está seguro de que desea eliminar este lote de NCF?
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Esta acción no se puede deshacer. Se eliminará la secuencia <strong>{{ $item->series }}{{ $item->type->code }}</strong> desde el {{ $item->from }} hasta el {{ $item->to }}.
            </p>
            <div class="mt-6 flex justify-end">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="error" class="ml-3">Eliminar Lote</x-ui.button>
            </div>
        </form>
    </x-modal>
@endif
@endforeach