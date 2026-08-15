    {{-- MODAL CREAR --}}
    <x-modal name="crear-unit" maxWidth="md">

        <x-form-header
            title="Nueva Unidad de Medida"
            subtitle="Registre una nueva unidad de medida."
            :back-route="route('inventory.products.units.index')" />

        <form action="{{ route('inventory.products.units.store') }}" method="POST" class="p-6">
            
            @csrf

            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Nombre de la nueva unidad de medida" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                </div>

                <div>
                    <x-input-label for="abbreviation" value="Abvreviación de la unidad de medida" />
                    <x-text-input id="abbreviation" name="abbreviation" type="text" class="mt-1 block w-full" required />
                </div>

                <div>
                    <x-input-label value="Estado Operativo" />
                    
                    {{-- Contenedor con w-full para ocupar todo el ancho --}}
                    <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                        
                        {{-- Opción Activo --}}
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="1" class="peer hidden"
                                {{ old('is_active', $unit->is_active ?? '1') == '1' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all
                                text-gray-500 hover:text-gray-700
                                peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm">
                                Activo
                            </span>
                        </label>

                        {{-- Opción Inactivo --}}
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="0" class="peer hidden"
                                {{ old('is_active', $unit->is_active ?? '1') == '0' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all
                                text-gray-500 hover:text-gray-700
                                peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-sm">
                                Inactivo
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-green-600">Guardar Unidad de Medida</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    @foreach($units as $item)
    <x-modal name="edit-unit-{{ $item->id }}" maxWidth="md">

        <x-form-header
            title="Editar Unidad de Medida: {{ $item->name }}"
            subtitle="Modifique la informacion de la unidad de medida."
            :back-route="route('inventory.products.units.index')" />

        <form method="POST" action="{{ route('inventory.products.units.update', $item) }}" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-input-label value="Nombre de la unidad de medida" />
                    <x-text-input name="name" type="text" class="mt-1 block w-full" value="{{ $item->name }}" required />
                </div>

                <div>
                    <x-input-label for="abbreviation" value="Abvreviación de la unidad de medida" />
                    <x-text-input id="abbreviation" name="abbreviation" type="text" class="mt-1 block w-full" value="{{ $item->abbreviation }}" required />
                </div>


                <div>
                    <x-input-label value="Estado Operativo" />
                    
                    {{-- Contenedor con w-full para ocupar todo el ancho --}}
                    <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                        
                        {{-- Opción Activo --}}
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="1" class="peer hidden"
                                {{ old('is_active', $item->is_active ?? '1') == '1' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all
                                text-gray-500 hover:text-gray-700
                                peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm">
                                Activo
                            </span>
                        </label>

                        {{-- Opción Inactivo --}}
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="0" class="peer hidden"
                                {{ old('is_active', $item->is_active ?? '1') == '0' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all
                                text-gray-500 hover:text-gray-700
                                peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-sm">
                                Inactivo
                            </span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-green-600">Actualizar Unidad de Medida</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-ui.confirm-deletion-modal 
    :id="$item->id"
    :title="'¿Eliminar Unidad de medida?'"
    :itemName="$item->name"
    :type="'la unidad de medida'"
    :route="route('inventory.products.units.destroy', $item)"
    />
    @endforeach