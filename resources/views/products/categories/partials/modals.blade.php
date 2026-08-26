    {{-- MODAL CREAR --}}
    <x-modal name="crear-category" maxWidth="md">

        <x-form-header
            title="Nueva Categoría de Producto"
            subtitle="Registre una nueva categoría de producto."
            :back-route="route('inventory.products.categories.index')" />

        <form action="{{ route('inventory.products.categories.store') }}" method="POST" class="p-6">
            
            @csrf

            <div class="space-y-4">
                <x-ui.forms.input
                    label="Nombre de la nueva categoría"
                    name="name"
                    :error="$errors->first('name')"
                    required
                />

                <div class="flex flex-col gap-2">
                    <x-ui.forms.radio
                        label="Activo"
                        name="is_active"
                        id="is_active_1"
                        value="1"
                        :checked="old('is_active', $category->is_active ?? '1') == '1'"
                    />
                    <x-ui.forms.radio
                        label="Inactivo"
                        name="is_active"
                        id="is_active_0"
                        value="0"
                        :checked="old('is_active', $category->is_active ?? '1') == '0'"
                    />
                </div>

                <x-ui.forms.textarea
                    label="Descripción Descriptiva"
                    name="description"
                    :rows="3"
                    placeholder="Descripción de la categoría..."
                    :error="$errors->first('description')"
                ></x-ui.forms.textarea>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Guardar Categoría</x-ui.button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    @foreach($categories as $item)
    <x-modal name="edit-category-{{ $item->id }}" maxWidth="md">

        <x-form-header
            title="Editar Categoría: {{ $item->name }}"
            subtitle="Modifique la informacion de la categoría."
            :back-route="route('inventory.products.categories.index')" />

        <form method="POST" action="{{ route('inventory.products.categories.update', $item) }}" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-4">
                <x-ui.forms.input
                    label="Nombre de la categoría"
                    name="name"
                    value="{{ $item->name }}"
                    :error="$errors->first('name')"
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

                <x-ui.forms.textarea
                    label="Descripción"
                    name="description"
                    :rows="3"
                    placeholder="Descripción de la categoría..."
                    :error="$errors->first('description')"
                >{{ old('description', $item->description) }}</x-ui.forms.textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Categoría</x-ui.button>
            </div>
        </form>
    </x-modal>

    <x-ui.confirm-deletion-modal 
    :id="$item->id"
    :title="'¿Eliminar Categoría de Producto?'"
    :itemName="$item->name"
    :type="'la categoría de producto'"
    :route="route('inventory.products.categories.destroy', $item)"
    />
    @endforeach