    {{-- MODAL CREAR --}}
    <x-modal name="crear-tipoEquipo" maxWidth="md">

        <x-form-header
            title="Nuevo Tipo de Equipo"
            subtitle="Registre un nuevo tipo de equipo."
            :back-route="route('clients.equipmentTypes.index')" />

        <form action="{{ route('clients.equipmentTypes.store') }}" method="POST" class="p-6">
            
            @csrf

            <div class="space-y-4">
                <div>
                    <x-ui.forms.input
                        id="nombre"
                        name="nombre"
                        type="text"
                        label="Nombre del Tipo de Equipo"
                        :error="$errors->first('nombre')"
                        required
                    />
                </div>

                <div>
                    <x-ui.forms.select label="Estado Operativo" name="activo" placeholder="">
                        <option value="1" {{ old('activo', '1') == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo</option>
                    </x-ui.forms.select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Guardar Tipo de Equipo</x-ui.button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    @foreach($equipmentsTypes as $item)
    <x-modal name="edit-tipoEquipo-{{ $item->id }}" maxWidth="md">

        <x-form-header
            title="Editar Tipo de Equipo: {{ $item->nombre }}"
            subtitle="Modifique la informacion del tipo de equipo."
            :back-route="route('clients.equipmentTypes.index')" />

        <form method="POST" action="{{ route('clients.equipmentTypes.update', $item) }}" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-ui.forms.input
                        name="nombre"
                        type="text"
                        label="Nombre"
                        value="{{ $item->nombre }}"
                        :error="$errors->first('nombre')"
                        required
                    />
                </div>

                <div>
                    <x-ui.forms.select label="Estado Operativo" name="activo" placeholder="">
                        <option value="1" {{ old('activo', '1') == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo / Mantenimiento</option>
                    </x-ui.forms.select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Tipo de Equipo</x-ui.button>
            </div>
        </form>
    </x-modal>

    <x-ui.confirm-deletion-modal 
    :id="$item->id"
    :title="'¿Eliminar Tipo de Equipo?'"
    :itemName="$item->nombre"
    :type="'el tipo de equipo'"
    :route="route('clients.equipmentTypes.destroy', $item)"
    />
    @endforeach