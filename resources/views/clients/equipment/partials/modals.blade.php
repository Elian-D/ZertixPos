@foreach($equipments as $item)

{{-- MODAL VER EQUIPO --}}
<x-modal name="view-equipment-{{ $item->id }}" maxWidth="2xl">
    <div class="overflow-hidden rounded-xl">
        {{-- Header con degradado y Estado --}}
        <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b relative">
            <div class="flex justify-between items-start">
                <div class="flex gap-4 items-center">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 shadow-sm">
                        <x-heroicon-s-cube class="w-7 h-7"/>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $item->name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-semibold px-2 py-0.5 bg-gray-200 text-gray-700 rounded uppercase tracking-wider">
                                {{ $item->code }}
                            </span>
                            <span class="text-gray-400 text-xs">•</span>
                            <span class="text-sm text-gray-500 font-medium italic">
                                {{ $item->equipmentType->nombre ?? 'Tipo no definido' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Badge de Estado Dinámico --}}
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ring-1 ring-inset shadow-sm 
                    {{ $item->active ? 'bg-emerald-100 text-emerald-700 ring-emerald-600/20' : 'bg-red-100 text-red-700 ring-red-600/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-2 {{ $item->active ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></span>
                    {{ $item->active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>

        <div class="p-8 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                {{-- Columna Izquierda: Información Técnica --}}
                <div class="space-y-6">
                    <section>
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <x-heroicon-s-identification class="w-4 h-4"/> Detalles Técnicos
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-4">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Número de Serial</span>
                                <p class="text-sm font-mono font-semibold text-gray-700">{{ $item->serial_number ?? 'S/N' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Modelo</span>
                                <p class="text-sm font-medium text-gray-700">{{ $item->model ?? 'Genérico' }}</p>
                            </div>
                        </div>
                    </section>

                    @if($item->notes)
                    <section>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <x-heroicon-s-chat-bubble-left-right class="w-4 h-4"/> Notas
                        </h4>
                        <p class="text-xs text-gray-600 leading-relaxed italic bg-amber-50 p-3 rounded-lg border border-amber-100 text-pretty">
                            "{{ $item->notes }}"
                        </p>
                    </section>
                    @endif
                </div>

                {{-- Columna Derecha: Asignación y Ubicación --}}
                <div class="space-y-6">
                    <section>
                        <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <x-heroicon-s-building-storefront class="w-4 h-4"/> Ubicación Actual
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-3">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase block">Punto de Venta</span>
                                <p class="text-sm font-bold text-gray-800">{{ $item->pointOfSale->name ?? 'No asignado' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase block">Dirección de Operación</span>
                                <p class="text-sm text-gray-600 leading-snug">{{ $item->pointOfSale->address ?? 'N/A' }}</p>
                                <p class="text-[11px] text-gray-400 mt-1 italic">{{ $item->pointOfSale->city ?? '' }}, {{ $item->pointOfSale->provincia->name ?? '' }}</p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <x-heroicon-s-clock class="w-4 h-4"/> Registro
                        </h4>
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-400">Fecha creación:</span>
                                <span class="font-medium text-gray-600">{{ $item->created_at->format('d/m/Y h:i A') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-400">Última actualización:</span>
                                <span class="font-medium text-gray-600">{{ $item->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Footer con acciones --}}
            <div class="mt-10 pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-[10px] text-gray-300 uppercase tracking-tighter font-mono">Equipo ID: {{ $item->id }}</div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <x-secondary-button x-on:click="$dispatch('close')" class="flex-1 sm:flex-none justify-center">
                        Cerrar
                    </x-secondary-button>
                    
                    <a href="{{ route('clients.equipment.edit', $item) }}" 
                       class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 transition duration-150 shadow-md shadow-indigo-100">
                        <x-heroicon-s-pencil class="w-4 h-4 mr-2" /> Editar Equipo
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-modal>

    <x-ui.confirm-deletion-modal 
        :id="$item->id"
        :title="'¿Eliminar Equipo?'"
        :itemName="$item->name"
        :type="'el equipo'"
        :route="route('clients.equipment.destroy', $item)"
        >
        <strong>Aviso:</strong> Esta operación se puede deshacer desde la papelera.
    </x-ui.confirm-deletion-modal>
@endforeach
