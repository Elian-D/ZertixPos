    {{-- MODAL CREAR --}}
    <x-modal name="crear-unit" maxWidth="md">

        <x-form-header
            title="Nueva Unidad de Medida"
            subtitle="Registre una nueva unidad de medida."
            :back-route="route('inventory.products.units.index')" />

        <form action="{{ route('inventory.products.units.store') }}" method="POST" class="p-6">
            
            @csrf

            <div class="space-y-4">
                <x-ui.forms.input
                    label="Nombre de la nueva unidad de medida"
                    name="name"
                    :error="$errors->first('name')"
                    required
                />

                <x-ui.forms.input
                    label="Abvreviación de la unidad de medida"
                    name="abbreviation"
                    :error="$errors->first('abbreviation')"
                    hint="Debe ser única en el sistema — se usa como código corto en reportes y tickets (ej. UND, LB, GAL)"
                    required
                />

                <div class="flex flex-col gap-2">
                    <x-ui.forms.radio
                        label="Activo"
                        name="is_active"
                        id="is_active_1"
                        value="1"
                        :checked="old('is_active', $unit->is_active ?? '1') == '1'"
                    />
                    <x-ui.forms.radio
                        label="Inactivo"
                        name="is_active"
                        id="is_active_0"
                        value="0"
                        :checked="old('is_active', $unit->is_active ?? '1') == '0'"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Guardar Unidad de Medida</x-ui.button>
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
                <x-ui.forms.input
                    label="Nombre de la unidad de medida"
                    name="name"
                    value="{{ $item->name }}"
                    :error="$errors->first('name')"
                    required
                />

                <x-ui.forms.input
                    label="Abvreviación de la unidad de medida"
                    name="abbreviation"
                    value="{{ $item->abbreviation }}"
                    :error="$errors->first('abbreviation')"
                    hint="Debe ser única en el sistema — se usa como código corto en reportes y tickets (ej. UND, LB, GAL)"
                    required
                />

                <div class="flex flex-col gap-2">
                    <x-ui.forms.radio
                        label="Activo"
                        name="is_active"
                        id="is_active_1_{{ $item->id }}"
                        value="1"
                        :checked="old('is_active', $item->is_active ?? '1') == '1'"
                    />
                    <x-ui.forms.radio
                        label="Inactivo"
                        name="is_active"
                        id="is_active_0_{{ $item->id }}"
                        value="0"
                        :checked="old('is_active', $item->is_active ?? '1') == '0'"
                    />
                </div>

            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Unidad de Medida</x-ui.button>
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