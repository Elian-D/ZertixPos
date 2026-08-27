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
                    <x-ui.forms.input
                        id="nombre"
                        name="nombre"
                        type="text"
                        label="Nombre del Tipo de Negocio"
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
                        <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo </option>
                    </x-ui.forms.select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Tipo de Negocio</x-ui.button>
            </div>
        </form>
    </x-modal>

    @if($item->trashed())
        {{-- Papelera (docs/analisis/politica-soft-deletes.md §6) — borrado
             definitivo vía wireConfirm, dispara BusinessTypeTable::forceDelete(). --}}
        <x-ui.confirm-deletion-modal
        :id="$item->id"
        :title="'¿Eliminar Permanentemente?'"
        :itemName="$item->nombre"
        :type="'el tipo de negocio'"
        :wireConfirm="'forceDelete(' . $item->id . ')'"
        :description="'Estás a punto de borrar definitivamente el tipo de negocio <strong>' . e($item->nombre) . '</strong>.'"
        >
        <strong>Aviso Crítico:</strong> Esta operación borrará todos los datos asociados y no se puede deshacer.
        </x-ui.confirm-deletion-modal>
    @else
        <x-ui.confirm-deletion-modal
        :id="$item->id"
        :title="'¿Eliminar Tipo de Negocio?'"
        :itemName="$item->nombre"
        :type="'el tipo de negocio'"
        :route="route('clients.businessTypes.destroy', $item)"
        />
    @endif
    @endforeach