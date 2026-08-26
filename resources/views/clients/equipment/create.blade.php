<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <form action="{{ route('clients.equipment.store') }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf


            <x-form-header
                title="Nuevo Equipo"
                subtitle="Registre un nuevo activo para asignarlo a un punto de venta."
                :back-route="route('clients.equipment.index')" />

            <div class="p-8 space-y-10">

                {{-- Sección 1: Identificación y Tipo --}}
                <section>
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                        <div class="w-7 h-7 bg-zertix-primary-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Identificación del Equipo</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-ui.forms.input
                                label="Nombre descriptivo del Equipo"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Ej: Freezer Vertical Puerta Cristal"
                                :error="$errors->first('name')"
                                required
                            />
                        </div>

                        <div>
                            <x-ui.forms.select
                                label="Tipo de Equipo"
                                name="equipment_type_id"
                                placeholder="Seleccione tipo..."
                                :error="$errors->first('equipment_type_id')"
                                required
                            >
                                @foreach($equipmentTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('equipment_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->nombre }} ({{ $type->prefix }})
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>

                        <div>
                            <x-ui.forms.select
                                label="Punto de Venta Asignado"
                                name="point_of_sale_id"
                                placeholder="Seleccione un local..."
                                :error="$errors->first('point_of_sale_id')"
                                required
                            >
                                @foreach($pointsOfSale as $pos)
                                    <option value="{{ $pos->id }}" {{ old('point_of_sale_id') == $pos->id ? 'selected' : '' }}>
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
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Especificaciones Técnicas</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-ui.forms.input
                                label="Número de Serial"
                                name="serial_number"
                                value="{{ old('serial_number') }}"
                                placeholder="S/N-000000"
                                :error="$errors->first('serial_number')"
                            />
                        </div>

                        <div>
                            <x-ui.forms.input
                                label="Modelo"
                                name="model"
                                value="{{ old('model') }}"
                                placeholder="Ej: MTC-500"
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
                                <option value="1" {{ old('active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('active') == '0' ? 'selected' : '' }}>Inactivo / Mantenimiento</option>
                            </x-ui.forms.select>
                        </div>
                    </div>
                </section>

                {{-- Sección 3: Notas --}}
                <section>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 bg-gray-600 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
                        <h3 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Notas Adicionales</h3>
                    </div>
                    <x-ui.forms.textarea
                        name="notes"
                        :rows="3"
                        placeholder="Detalles sobre el estado físico, historial, etc..."
                        :error="$errors->first('notes')"
                    >{{ old('notes') }}</x-ui.forms.textarea>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <a href="{{ route('clients.equipment.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                <x-ui.button type="submit" variant="primary" class="shadow-lg px-8">Guardar Equipo</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
