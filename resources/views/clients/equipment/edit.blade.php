<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <form action="{{ route('clients.equipment.update', $equipment) }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf @method('PUT')

            
            <x-form-header
                title="Editar Equipo: {{ $equipment->code }}"
                subtitle="Modifique la información técnica o la asignación del equipo."
                :back-route="route('clients.equipment.index')" />

            <div class="p-8 space-y-10">
                {{-- Sección 1: Identificación --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Identificación</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label value="Nombre del Equipo" />
                            <x-text-input name="name" class="w-full mt-1" :value="old('name', $equipment->name)" required />
                        </div>

                        {{-- En la Sección 1: Identificación --}}
                        <div>
                            <x-input-label value="Tipo de Equipo" />
                            
                            @can('equipment regenerate-code')
                                {{-- Usuario Admin: Puede cambiar tipo y el checkbox decide si regenera el código --}}
                                <div class="space-y-2 mt-1">
                                    <select name="equipment_type_id" class="w-full rounded-md border-amber-300 bg-amber-50 shadow-sm focus:ring-indigo-500 text-sm">
                                        @foreach($equipmentTypes as $type)
                                            <option value="{{ $type->id }}" {{ old('equipment_type_id', $equipment->equipment_type_id) == $type->id ? 'selected' : '' }}>
                                                {{ $type->nombre }} ({{ $type->prefix }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-amber-600 mt-1 italic">* Tienes permiso para cambiar el tipo. El código se regenerará al actualizar.</p>
                                </div>
                            @else
                                {{-- Usuario Normal: Solo lectura --}}
                                <select disabled class="w-full mt-1 rounded-md border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed shadow-sm text-sm">
                                    @foreach($equipmentTypes as $type)
                                        <option value="{{ $type->id }}" {{ $equipment->equipment_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="equipment_type_id" value="{{ $equipment->equipment_type_id }}">
                                <p class="text-[10px] text-gray-400 mt-1 italic">* Para cambiar el tipo, contacte al administrador.</p>
                            @endcan
                        </div>

                        <div>
                            <x-input-label value="Punto de Venta Asignado" />
                            <select name="point_of_sale_id" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                                @foreach($pointsOfSale as $pos)
                                    <option value="{{ $pos->id }}" {{ old('point_of_sale_id', $equipment->point_of_sale_id) == $pos->id ? 'selected' : '' }}>
                                        {{ $pos->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Sección 2: Detalles Técnicos --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-xs">2</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Especificaciones</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-input-label value="Número de Serial" />
                            <x-text-input name="serial_number" class="w-full mt-1" :value="old('serial_number', $equipment->serial_number)" />
                        </div>

                        <div>
                            <x-input-label value="Modelo" />
                            <x-text-input name="model" class="w-full mt-1" :value="old('model', $equipment->model)" />
                        </div>

                        <div>
                            <x-input-label value="Estado Operativo" />
                            <select name="active" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                                <option value="1" {{ old('active', $equipment->active) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('active', $equipment->active) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Sección 3: Notas --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 bg-gray-600 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Notas</h3>
                    </div>
                    <textarea name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">{{ old('notes', $equipment->notes) }}</textarea>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <a href="{{ route('clients.equipment.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-ui.button type="submit" variant="primary" class="shadow-lg px-8">Actualizar Equipo</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>