{{-- MODAL CREAR AJUSTE --}}
<x-modal name="create-adjustment" maxWidth="md">
    <x-form-header 
        title="Registro de Movimiento Manual" 
        subtitle="Gestione entradas, salidas o transferencias de stock." />

    {{-- Inicializamos Alpine.js para controlar el estado del formulario --}}
    <form action="{{ route('inventory.movements.store') }}" 
          method="POST" 
          class="p-6"
          x-data="{ 
            type: 'adjustment',
            originId: '',
            quantity: '',
            get helperText() {
                if (this.type === 'input') return 'Solo se permiten números positivos para entradas.';
                if (this.type === 'output') return 'Ingrese la cantidad positiva; el sistema realizará la resta.';
                if (this.type === 'transfer') return 'La cantidad se restará del origen y se sumará al destino.';
                return 'Use números negativos para restar stock y positivos para sumar.';
            }
          }">
        @csrf
        
        <div class="space-y-4">
            {{-- 1. Tipo de Movimiento (Primero para definir el flujo) --}}
            <div>
                <x-input-label for="type" value="Tipo de Operación" />
                <select name="type" id="type" x-model="type"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="adjustment">Ajuste Manual</option>
                    <option value="input">Entrada Adicional</option>
                    <option value="output">Salida / Merma</option>
                    <option value="transfer">Transferencia entre Almacenes</option>
                </select>
            </div>

            {{-- 2. Producto --}}
            <div>
                <x-input-label for="product_id" value="Producto" />
                <select name="product_id" id="product_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                    <option value="">Seleccione un producto...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4" :class="type === 'transfer' ? 'sm:grid-cols-2' : ''">
                {{-- 3. Almacén Origen --}}
                <div>
                    <x-input-label for="warehouse_id" x-text="type === 'transfer' ? 'Almacén Origen' : 'Almacén'" />
                    <select name="warehouse_id" id="warehouse_id" x-model="originId"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        <option value="">Seleccione...</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Almacén Destino (Dinámico para Transferencias) --}}
                <template x-if="type === 'transfer'">
                    <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
                        <x-input-label for="to_warehouse_id" value="Almacén Destino" />
                        <select name="to_warehouse_id" id="to_warehouse_id" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                            <option value="">Seleccione destino...</option>
                            @foreach($warehouses as $wh)
                                {{-- Validamos que no sea el mismo que el de origen usando Alpine --}}
                                <option value="{{ $wh->id }}" x-show="originId != {{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>
            </div>

            {{-- 5. Cantidad con validaciones dinámicas --}}
            <div>
                <x-input-label for="quantity" value="Cantidad" />
                <x-text-input id="quantity" 
                    name="quantity" 
                    type="number" 
                    step="0.01" 
                    x-model="quantity"
                    ::min="type !== 'adjustment' ? '0.01' : ''"
                    class="mt-1 block w-full" 
                    placeholder="0.00" 
                    required />
                <p class="mt-1 text-[10px] italic font-medium" 
                   :class="type === 'adjustment' ? 'text-blue-500' : 'text-amber-600'" 
                   x-text="helperText"></p>
            </div>

            {{-- 6. Descripción --}}
            <div>
                <x-input-label for="description" value="Motivo / Descripción" />
                <textarea name="description" id="description" rows="2" 
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                    placeholder="Ej: Ajuste por rotura o transferencia de stock excedente..." required></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
            <x-ui.button type="submit" variant="primary">Registrar Movimiento</x-ui.button>
        </div>
    </form>
</x-modal>

@foreach($items as $item)
    <x-modal name="view-movement-{{ $item->id }}" maxWidth="lg">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con Color según Tipo --}}
            @php
                $headerColors = match($item->type) {
                    'input' => 'from-green-50 to-white border-green-100 text-green-700',
                    'output' => 'from-red-50 to-white border-red-100 text-red-700',
                    'transfer' => 'from-blue-50 to-white border-blue-100 text-blue-700',
                    default => 'from-gray-50 to-white border-gray-100 text-gray-700',
                };

                // Lógica para determinar el rol del almacén en una transferencia
                $isTransfer = $item->type === 'transfer';
                $isTransferOutput = $isTransfer && $item->quantity < 0;
                $isTransferInput = $isTransfer && $item->quantity > 0;
            @endphp
            
            <div class="bg-gradient-to-r {{ $headerColors }} px-6 py-4 border-b relative">
                <div class="flex justify-between items-center">
                    <div class="flex gap-3 items-center">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-white shadow-sm border border-current opacity-80">
                            @if($isTransfer)
                                <x-heroicon-s-arrows-right-left class="w-6 h-6"/>
                            @elseif($item->type === 'input')
                                <x-heroicon-s-arrow-trending-up class="w-6 h-6"/>
                            @else
                                <x-heroicon-s-adjustments-horizontal class="w-6 h-6"/>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-bold leading-tight">Movimiento #{{ $item->id }}</h3>
                            <p class="text-xs font-medium opacity-70 italic">
                                {{ $isTransferOutput ? 'Transferencia (Salida)' : ($isTransferInput ? 'Transferencia (Entrada)' : ($types[$item->type] ?? $item->type)) }}
                            </p>
                        </div>
                    </div>
                    <span class="text-[10px] font-mono bg-white/50 px-2 py-1 rounded border border-current/20">
                        {{ $item->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="p-6 bg-white">
                <div class="space-y-6">
                    {{-- Información Principal --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <span class="text-[10px] text-gray-400 uppercase font-bold block">Producto</span>
                            <p class="text-sm font-semibold text-gray-800">{{ $item->product->name }}</p>
                        </div>

                        {{-- Lógica de Almacenes Dinámica --}}
                        @if($isTransfer)
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                <span class="text-[10px] text-blue-400 uppercase font-bold block">
                                    {{ $isTransferOutput ? 'Almacén Destino' : 'Almacén Origen' }}
                                </span>
                                <p class="text-sm font-semibold text-blue-800">
                                    {{-- Si es salida usamos to_warehouse, si es entrada buscamos la referencia del padre --}}
                                    @if($isTransferOutput)
                                        {{ $item->toWarehouse->name ?? 'No especificado' }}
                                    @else
                                        {{-- Intentamos extraer el nombre del almacén desde la descripción o relación si existe --}}
                                        {{ str_replace('Entrada por transferencia desde: ', '', explode('.', $item->description)[0]) }}
                                    @endif
                                </p>
                            </div>
                        @else
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Almacén Actual</span>
                                <p class="text-sm font-semibold text-gray-800">{{ $item->warehouse->name }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Flujo de Stock --}}
                    <div class="py-6 border-y border-dashed border-gray-200">
                        <div class="flex items-center justify-around">
                            <div class="text-center">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Stock Inicial</span>
                                <span class="text-xl font-semibold text-gray-500">{{ number_format($item->previous_stock, 2) }}</span>
                            </div>
                            
                            <div class="flex flex-col items-center">
                                <x-heroicon-s-chevron-double-right class="w-5 h-5 text-gray-300" />
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $item->quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $item->quantity > 0 ? '+' : '' }}{{ number_format($item->quantity, 2) }}
                                </span>
                            </div>

                            <div class="text-center">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Stock Resultante</span>
                                <span class="text-2xl font-black text-indigo-700">{{ number_format($item->current_stock, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Alerta de Transferencia --}}
                    @if($isTransfer)
                        <div class="p-3 bg-blue-50 rounded-lg border border-blue-100 flex items-start gap-3">
                            <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"/>
                            <p class="text-[11px] text-blue-700 leading-relaxed">
                                <strong>Nota de Transferencia:</strong> Este registro refleja solo una parte de la operación. 
                                El stock fue movido entre <strong>{{ $item->warehouse->name }}</strong> y el almacén destino/origen correspondiente.
                            </p>
                        </div>
                    @endif

                    {{-- Auditoría y Descripción --}}
                    <div class="space-y-4">
                        <section>
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                                <x-heroicon-s-document-text class="w-4 h-4 text-gray-300"/> Motivo / Descripción
                            </h4>
                            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg min-h-[60px]">
                                {{ $item->description ?? 'Sin descripción registrada.' }}
                            </p>
                        </section>

                        <section class="flex justify-between items-end border-t pt-4">
                            <div>
                                <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                                    <x-heroicon-s-user class="w-3 h-3"/> Responsable
                                </h4>
                                <p class="text-xs font-medium text-gray-700">{{ $item->user->name ?? 'Sistema' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 italic">
                                    Ref: {{ $item->reference_type ? basename($item->reference_type) . ' #' . $item->reference_id : 'Manual' }}
                                </p>
                            </div>
                        </section>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center">
                            Cerrar Detalle
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>
@endforeach