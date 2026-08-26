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

<x-modal name="view-sequence-{{ $item->id }}" maxWidth="lg">
    <div class="overflow-hidden rounded-xl bg-white">
        <div class="bg-gradient-to-r from-zertix-primary-600 to-zertix-primary-800 px-6 py-4 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold italic">{{ $item->type->name }}</h3>
                    <p class="text-xs opacity-80 uppercase tracking-widest font-mono">
                        {{-- Ajuste: Padding dinámico basado en is_electronic --}}
                        {{ $item->series }}{{ $item->type->code }}{{ str_pad($item->from, $item->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
                <div class="text-right">
                    <x-ui.badge :variant="match($item->calculated_status) {
                            \App\Models\Sales\Ncf\NcfSequence::STATUS_ACTIVE => 'success',
                            \App\Models\Sales\Ncf\NcfSequence::STATUS_EXHAUSTED => 'warning',
                            \App\Models\Sales\Ncf\NcfSequence::STATUS_EXPIRED => 'error',
                            default => 'slate',
                        }" size="sm" :dot="false">
                        {{ $item->status_label }}
                    </x-ui.badge>
                </div>
            </div>
        </div>

        <div class="p-6">
            {{-- Grid de contadores --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-[10px] text-gray-400 uppercase font-bold block">Total</span>
                    <span class="text-lg font-bold">{{ number_format($item->to - $item->from + 1) }}</span>
                </div>
                <div class="text-center p-3 bg-zertix-primary-50 rounded-lg border border-zertix-primary-100">
                    <span class="text-[10px] text-zertix-primary-400 uppercase font-bold block">Usados</span>
                    <span class="text-lg font-bold text-zertix-primary-700">{{ number_format($item->current - $item->from + 1) }}</span>
                </div>
                <div class="text-center p-3 {{ ($item->to - $item->current) <= 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg">
                    <span class="text-[10px] {{ ($item->to - $item->current) <= 0 ? 'text-red-400' : 'text-green-400' }} uppercase font-bold block">Disponibles</span>
                    <span class="text-lg font-bold {{ ($item->to - $item->current) <= 0 ? 'text-red-700' : 'text-green-700' }}">
                        {{ number_format(max(0, $item->to - $item->current)) }}
                    </span>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between text-sm border-b pb-2">
                    <span class="text-gray-500">Próximo NCF a emitir:</span>
                    <span class="font-mono font-bold text-gray-800">
                        {{-- Ajuste: Padding dinámico en el próximo número --}}
                        {{ $item->series }}{{ $item->type->code }}{{ str_pad($item->current + 1, $item->type->is_electronic ? 10 : 8, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                
                {{-- ... resto de los campos (Fecha registro, Vencimiento) ... --}}
                <div class="flex justify-between text-sm border-b pb-2">
                    <span class="text-gray-500">Fecha de Registro:</span>
                    <span class="text-gray-800">{{ $item->created_at->format('d/m/Y h:i A') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Vence el:</span>
                    <span class="font-bold {{ $item->expiry_date->isPast() ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $item->expiry_date->format('d/m/Y') }}
                    </span>
                </div>

                {{-- Formulario de Umbral --}}
                <form action="{{ route('finance.ncf.sequences.update-threshold', $item->id) }}" method="POST" class="mt-4 pt-4 border-t">
                    @csrf
                    @method('PATCH')
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <x-ui.forms.input type="number" label="Cambiar Umbral de Alerta" name="alert_threshold"
                                value="{{ $item->alert_threshold }}" />
                        </div>
                        <x-ui.button type="submit" variant="primary" class="py-2 px-3 text-[10px]">
                            Actualizar
                        </x-ui.button>
                    </div>
                </form>
            </div>

            <div class="mt-8 flex justify-end">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="w-full sm:w-auto">
                    Cerrar Detalle
                </x-ui.button>
            </div>
        </div>
    </div>
</x-modal>
@endforeach