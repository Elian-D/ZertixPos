@foreach($stocks as $item)
    <x-modal name="edit-min-stock-{{ $item->id }}" maxWidth="sm">
        <x-form-header
            title="Configurar Alerta"
            subtitle="Define el stock mínimo para {{ $item->product->name }} en {{ $item->warehouse->name }}."
            :back-route="null" />

        <form method="POST" action="{{ route('inventory.stocks.update-min-stock', $item) }}" class="p-6">
            @csrf @method('PATCH')

            <div>
                <x-input-label value="Stock Mínimo de Alerta" />
                <div class="mt-1 relative rounded-md shadow-sm">
                    <x-text-input 
                        name="min_stock" 
                        type="number" 
                        step="0.01" 
                        min="0"
                        class="block w-full pr-12" 
                        value="{{ $item->min_stock }}" 
                        required 
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400 text-xs">
                        {{ $item->product->unit->abbreviation }}
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 italic">
                    El sistema te notificará o marcará en rojo cuando el stock sea igual o inferior a este valor.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Alerta</x-ui.button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL: DETALLES COMPLETOS DEL BALANCE --}}
    <x-modal name="view-stock-{{ $item->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con degradado según estado del Stock --}}
            @php
                $isLow = $item->quantity <= $item->min_stock;
                $isOut = $item->quantity <= 0;
                $headerGradient = $isOut ? 'from-red-50 to-white' : ($isLow ? 'from-amber-50 to-white' : 'from-emerald-50 to-white');
                $iconColor = $isOut ? 'text-red-700 bg-red-100' : ($isLow ? 'text-amber-700 bg-amber-100' : 'text-emerald-700 bg-emerald-100');
            @endphp

            <div class="bg-gradient-to-r {{ $headerGradient }} px-8 py-6 border-b relative">
                <div class="flex justify-between items-start">
                    <div class="flex gap-4 items-center">
                        <div class="w-12 h-12 {{ $iconColor }} rounded-xl flex items-center justify-center shadow-sm">
                            <x-heroicon-s-cube class="w-7 h-7"/>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $item->product->name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-semibold px-2 py-0.5 bg-gray-200 text-gray-700 rounded uppercase tracking-wider">
                                    {{ $item->product->sku ?? 'SIN SKU' }}
                                </span>
                                <span class="text-gray-400 text-xs">•</span>
                                <span class="text-sm text-gray-500 font-medium italic">{{ $item->product->category->name ?? 'Sin categoría' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Badge de Estado Dinámico --}}
                    <x-ui.badge :variant="$isOut ? 'error' : ($isLow ? 'warning' : 'success')" class="shadow-sm">
                        {{ $isOut ? 'Agotado' : ($isLow ? 'Stock Bajo' : 'Suficiente') }}
                    </x-ui.badge>
                </div>
            </div>

            <div class="p-8 bg-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Columna Izquierda: Almacén y Producto --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-zertix-primary-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-building-office class="w-4 h-4"/> Ubicación de Red
                            </h4>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Almacén / Sucursal</span>
                                <p class="text-sm font-semibold text-gray-700">{{ $item->warehouse->name }}</p>
                                <p class="text-[11px] text-gray-500 mt-1 italic">{{ $item->warehouse->address ?? 'Sin dirección registrada' }}</p>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-information-circle class="w-4 h-4"/> Detalles Técnicos
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between border-b border-gray-50 pb-2">
                                    <span class="text-xs text-gray-500">Unidad de Medida</span>
                                    <span class="text-xs font-bold text-gray-700">{{ $item->product->unit->name }} ({{ $item->product->unit->abbreviation }})</span>
                                </div>
                                <div class="flex justify-between border-b border-gray-50 pb-2">
                                    <span class="text-xs text-gray-500">Costo Unitario</span>
                                    <span class="text-xs font-bold text-gray-700">{{ config('regional.currency_symbol') }}{{ number_format($item->product->cost, 2) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-gray-50 pb-2">
                                    <span class="text-xs text-gray-500">Precio Venta</span>
                                    <span class="text-xs font-bold text-emerald-600">{{ config('regional.currency_symbol') }}{{ number_format($item->product->price_with_tax, 2) }}</span>
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- Columna Derecha: Gestión de Inventario (CON BARRA DE PROGRESO) --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-amber-50 pb-2">
                                <x-heroicon-s-cog-6-tooth class="w-4 h-4"/> Gestión de Logística
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 shadow-inner">
                                <div class="flex justify-between items-end mb-4">
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase block">Stock Disponible</span>
                                        <p class="text-3xl font-black {{ $isLow ? 'text-red-600' : 'text-gray-800' }}">
                                            {{ number_format($item->quantity, 2) }} 
                                            <span class="text-sm font-medium text-gray-400 tracking-tighter">{{ $item->product->unit->abbreviation }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] text-gray-400 uppercase block">Punto de Reorden</span>
                                        <p class="text-sm font-bold text-gray-600">{{ number_format($item->min_stock, 2) }}</p>
                                    </div>
                                </div>

                                {{-- Barra de progreso visual --}}
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                    @php 
                                        // Calculamos porcentaje basado en que el 100% sea el triple del mínimo para dar perspectiva
                                        $maxVisual = $item->min_stock > 0 ? $item->min_stock * 3 : 100;
                                        $percent = $item->quantity > 0 ? min(($item->quantity / $maxVisual) * 100, 100) : 0; 
                                    @endphp
                                    <div class="h-2 rounded-full transition-all duration-500 {{ $isLow ? 'bg-red-500' : 'bg-emerald-500' }}" 
                                         style="width: {{ $percent }}%"></div>
                                </div>
                                <p class="text-[10px] text-gray-400 italic text-center">
                                    {{ $isLow ? '⚠️ Requiere reposición inmediata' : '✓ Niveles dentro del rango operativo' }}
                                </p>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-clock class="w-4 h-4"/> Último Movimiento
                            </h4>
                            <div class="flex flex-col gap-2 italic">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-400">Última actualización:</span>
                                    <span class="font-medium text-gray-600">{{ $item->updated_at->format('d/m/Y h:i A') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-400">Hace:</span>
                                    <span class="font-medium text-gray-600">{{ $item->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                {{-- Footer con acciones --}}
                <div class="mt-10 pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-[10px] text-gray-300 uppercase tracking-tighter font-mono">STOCK_ID: {{ $item->id }}</div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="flex-1 sm:flex-none justify-center">
                            Cerrar Vista
                        </x-ui.button>
                        <button @click="$dispatch('close'); $dispatch('open-modal', 'edit-min-stock-{{ $item->id }}')" 
                                class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 bg-amber-500 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition shadow-md shadow-amber-100">
                            <x-heroicon-s-bell-alert class="w-4 h-4 mr-2" /> Ajustar Alerta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>
@endforeach