    {{-- MODAL CREAR --}}
    <x-modal name="crear-tipoNegocio" maxWidth="md">

        <x-form-header
            title="Nuevo Tipo de Negocio"
            subtitle="Registre un nuevo tipo de negocio."
            :back-route="route('clients.businessTypes.index')" />

        <form action="{{ route('clients.businessTypes.store') }}" method="POST" class="p-6">
            
            @csrf

            <div class="space-y-4">
                <div>
                    <x-input-label for="nombre" value="Nombre del Tipo de Negocio" />
                    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" required />
                </div>

                <div>
                    <x-input-label value="Estado Operativo" />
                    <select name="activo" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                        <option value="1" {{ old('activo', '1') == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Guardar Tipo de Negocio</x-ui.button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    @foreach($businessTypes as $item)
    <x-modal name="edit-tipoNegocio-{{ $item->id }}" maxWidth="md">

        <x-form-header
            title="Editar Tipo de Negocio: {{ $item->nombre }}"
            subtitle="Modifique la informacion del tipo de negocio."
            :back-route="route('clients.businessTypes.index')" />

        <form method="POST" action="{{ route('clients.businessTypes.update', $item) }}" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-input-label value="Nombre" />
                    <x-text-input name="nombre" type="text" class="mt-1 block w-full" value="{{ $item->nombre }}" required />
                </div>

                <div>
                    <x-input-label value="Estado Operativo" />
                    <select name="activo" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm">
                        <option value="1" {{ old('activo', '1') == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo </option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Tipo de Negocio</x-ui.button>
            </div>
        </form>
    </x-modal>

    <x-ui.confirm-deletion-modal 
    :id="$item->id"
    :title="'¿Eliminar Tipo de Negocio?'"
    :itemName="$item->nombre"
    :type="'el tipo de negocio'"
    :route="route('clients.businessTypes.destroy', $item)"
    />
    @endforeach