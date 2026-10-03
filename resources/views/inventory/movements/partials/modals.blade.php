{{-- Modal de detalle por fila del kardex (solo lectura). El modal de ajuste manual se eliminó en v1.5.0 REQ-1.5. --}}
@foreach($items as $item)
    <x-modal name="view-movement-{{ $item->id }}" maxWidth="lg">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con el color del tipo (InventoryMovement::TYPE_MAP) --}}
            @php
                $hex = $item->type_hex;
                $isTransfer = in_array($item->type, ['transfer_out', 'transfer_in']);
            @endphp

            <div class="px-6 py-4 border-b relative" style="background: linear-gradient(to right, {{ $hex }}14, #ffffff); border-color: {{ $hex }}33; color: {{ $hex }};">
                <div class="flex justify-between items-center">
                    <div class="flex gap-3 items-center">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-white shadow-sm border border-current opacity-80">
                            @if($isTransfer)
                                <x-heroicon-s-arrows-right-left class="w-6 h-6"/>
                            @elseif($item->quantity > 0)
                                <x-heroicon-s-arrow-trending-up class="w-6 h-6"/>
                            @else
                                <x-heroicon-s-arrow-trending-down class="w-6 h-6"/>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-bold leading-tight">Movimiento #{{ $item->id }}</h3>
                            <p class="text-xs font-medium opacity-70 italic">
                                {{ $item->type_label }}
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

                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <span class="text-[10px] text-gray-400 uppercase font-bold block">Almacén</span>
                            <p class="text-sm font-semibold text-gray-800">{{ $item->warehouse->name ?? '—' }}</p>
                        </div>

                        {{-- Transferencia: el otro almacén (destino al enviar, origen al recibir) --}}
                        @if($isTransfer && $item->toWarehouse)
                            <div class="col-span-2 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">
                                    {{ $item->type === 'transfer_out' ? 'Hacia' : 'Desde' }}
                                </span>
                                <p class="text-sm font-semibold text-gray-800">{{ $item->toWarehouse->name }}</p>
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
                                <span class="text-2xl font-black text-zertix-primary-700">{{ number_format($item->current_stock, 2) }}</span>
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
                                @php $origin = $item->origin; @endphp
                                <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Origen</h4>
                                @if($origin['url'])
                                    <a href="{{ $origin['url'] }}" class="text-xs font-medium text-zertix-primary-700 hover:underline">
                                        {{ $origin['label'] }} →
                                    </a>
                                @else
                                    <p class="text-xs font-medium text-gray-700">{{ $origin['label'] }}</p>
                                @endif
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