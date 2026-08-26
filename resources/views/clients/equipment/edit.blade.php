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
                        <div class="w-7 h-7 bg-zertix-primary-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Identificación</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-ui.forms.input
                                label="Nombre del Equipo"
                                name="name"
                                value="{{ old('name', $equipment->name) }}"
                                :error="$errors->first('name')"
                                required
                            />
                        </div>

                        {{-- En la Sección 1: Identificación --}}
                        <div>
                            @can('equipment regenerate-code')
                                {{-- Usuario Admin: Puede cambiar tipo y el checkbox decide si regenera el código --}}
                                <div class="space-y-2 mt-1">
                                    <x-ui.forms.select
                                        label="Tipo de Equipo"
                                        name="equipment_type_id"
                                        class="border-amber-300 bg-amber-50"
                                        :error="$errors->first('equipment_type_id')"
                                        required
                                    >
                                        @foreach($equipmentTypes as $type)
                                            <option value="{{ $type->id }}" {{ old('equipment_type_id', $equipment->equipment_type_id) == $type->id ? 'selected' : '' }}>
                                                {{ $type->nombre }} ({{ $type->prefix }})
                                            </option>
                                        @endforeach
                                    </x-ui.forms.select>
                                    <p class="text-[10px] text-amber-600 mt-1 italic">* Tienes permiso para cambiar el tipo. El código se regenerará al actualizar.</p>
                                </div>
                            @else
                                {{-- Usuario Normal: Solo lectura --}}
                                <x-ui.forms.select
                                    label="Tipo de Equipo"
                                    class="bg-gray-100 text-gray-500 cursor-not-allowed"
                                    disabled
                                >
                                    @foreach($equipmentTypes as $type)
                                        <option value="{{ $type->id }}" {{ $equipment->equipment_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->nombre }}
                                        </option>
                                    @endforeach
                                </x-ui.forms.select>
                                <input type="hidden" name="equipment_type_id" value="{{ $equipment->equipment_type_id }}">
                                <p class="text-[10px] text-gray-400 mt-1 italic">* Para cambiar el tipo, contacte al administrador.</p>
                            @endcan
                        </div>

                        <div>
                            <x-ui.forms.select
                                label="Punto de Venta Asignado"
                                name="point_of_sale_id"
                                :error="$errors->first('point_of_sale_id')"
                                required
                            >
                                @foreach($pointsOfSale as $pos)
                                    <option value="{{ $pos->id }}" {{ old('point_of_sale_id', $equipment->point_of_sale_id) == $pos->id ? 'selected' : '' }}>
                                        {{ $pos->name }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    </div>
                </section>

                {{-- Sección 2: Detalles Técnicos --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-zertix-primary-600 text-white rounded-full flex items-center justify-center font-bold text-xs">2</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Especificaciones</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-ui.forms.input
                                label="Número de Serial"
                                name="serial_number"
                                value="{{ old('serial_number', $equipment->serial_number) }}"
                                :error="$errors->first('serial_number')"
                            />
                        </div>

                        <div>
                            <x-ui.forms.input
                                label="Modelo"
                                name="model"
                                value="{{ old('model', $equipment->model) }}"
                                :error="$errors->first('model')"
                            />
                        </div>

                        <div>
                            <x-ui.forms.select
                                label="Estado Operativo"
                                name="active"
                                placeholder=""
                                :error="$errors->first('active')"
                            >
                                <option value="1" {{ old('active', $equipment->active) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('active', $equipment->active) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </x-ui.forms.select>
                        </div>
                    </div>
                </section>

                {{-- Sección 3: Notas --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 bg-gray-600 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Notas</h3>
                    </div>
                    <x-ui.forms.textarea
                        name="notes"
                        :rows="3"
                        :error="$errors->first('notes')"
                    >{{ old('notes', $equipment->notes) }}</x-ui.forms.textarea>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <a href="{{ route('clients.equipment.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-ui.button type="submit" variant="primary" class="shadow-lg px-8">Actualizar Equipo</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
