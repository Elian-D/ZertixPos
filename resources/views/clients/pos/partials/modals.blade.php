@foreach($pos as $item)
    {{-- MODAL: DETALLES COMPLETOS DEL POS --}}
    <x-modal name="view-pos-{{ $item->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con degradado y Estado Operativo --}}
            <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b relative">
                <div class="flex justify-between items-start">
                    <div class="flex gap-4 items-center">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-700 shadow-sm">
                            <x-heroicon-s-building-storefront class="w-7 h-7"/>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $item->name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-semibold px-2 py-0.5 bg-gray-200 text-gray-700 rounded uppercase tracking-wider">
                                    {{ $item->code }}
                                </span>
                                <span class="text-gray-400 text-xs">•</span>
                                <span class="text-sm text-gray-500 font-medium italic">{{ $item->businessType->nombre ?? 'Sin giro definido' }}</span>
                            </div>
                        </div>
                    </div>
                    {{-- Badge de Estado Dinámico --}}
                    <x-ui.badge :variant="$item->active ? 'success' : 'error'">
                        {{ $item->active ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </div>
            </div>

            <div class="p-8 bg-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {{-- Columna Izquierda: Vinculación y Contacto --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-user-group class="w-4 h-4"/> Cliente Vinculado
                            </h4>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Razón Social</span>
                                <p class="text-sm font-semibold text-gray-700">{{ $item->client->name ?? 'N/A' }}</p>
                                <p class="text-[11px] text-gray-500 mt-1 italic">{{ $item->client->tax_id ?? '' }}</p>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-phone class="w-4 h-4"/> Contacto en Sitio
                            </h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <x-heroicon-s-user class="w-5 h-5 text-gray-300"/>
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase block">Responsable</span>
                                        <p class="text-sm font-medium text-gray-700">{{ $item->contact_name ?? 'No asignado' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-heroicon-s-device-phone-mobile class="w-5 h-5 text-gray-300"/>
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase block">Teléfono / Celular</span>
                                        <p class="text-sm font-medium text-gray-700">{{ $item->contact_phone ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- Columna Derecha: Ubicación y Auditoría --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-map-pin class="w-4 h-4"/> Ubicación del POS
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-3">
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Provincia / Ciudad</span>
                                    <p class="text-sm font-medium">{{ $item->provincia->name ?? 'N/A' }}, {{ $item->city ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Dirección Exacta</span>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ $item->address }}</p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-clock class="w-4 h-4"/> Trazabilidad
                            </h4>
                            <div class="flex flex-col gap-2 italic">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-400">Fecha de creación:</span>
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
                    <div class="text-[10px] text-gray-300 uppercase tracking-tighter font-mono">ID: {{ $item->id }}</div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="flex-1 sm:flex-none justify-center">
                            Cerrar
                        </x-ui.button>
                        <a href="{{ route('clients.delivery_points.edit', $item) }}" class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 bg-zertix-primary-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-zertix-primary-700 active:bg-zertix-primary-900 transition duration-150 shadow-md shadow-zertix-primary-100">
                            <x-heroicon-s-pencil class="w-4 h-4 mr-2" /> Editar Punto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

    <x-ui.confirm-deletion-modal 
        :id="$item->id"
        :title="'¿Eliminar Punto de Venta?'"
        :itemName="$item->name"
        :type="'el punto de venta'"
        :route="route('clients.delivery_points.destroy', $item)"
        >
        <strong>Aviso:</strong> Esta operación se puede deshacer desde la papelera.
    </x-ui.confirm-deletion-modal>
@endforeach