{{-- MODAL CREAR --}}
    <x-modal name="crear-warehouse" maxWidth="md">

        <x-form-header
            title="Nuevo Almacén"
            subtitle="Registre una nueva ubicación de inventario (fija o móvil)."
            :back-route="route('inventory.warehouses.index')" />

        <form action="{{ route('inventory.warehouses.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-4">
                {{-- Nombre --}}
                <x-ui.forms.input
                    label="Nombre del almacén"
                    id="name"
                    name="name"
                    type="text"
                    placeholder="Ej: Bodega Central o Camión #01"
                    :error="$errors->first('name')"
                    required
                />

                {{-- Tipo de Almacén --}}
                <x-ui.forms.select
                    label="Tipo de ubicación"
                    name="type"
                    id="type"
                    :error="$errors->first('type')"
                    required
                >
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-ui.forms.select>

                {{-- Dirección/Ubicación --}}
                <x-ui.forms.input
                    label="Dirección o Referencia"
                    id="address"
                    name="address"
                    type="text"
                    placeholder="Dirección física o placa del vehículo"
                    :error="$errors->first('address')"
                />

                {{-- Descripción --}}
                <x-ui.forms.textarea
                    label="Descripción (Opcional)"
                    name="description"
                    id="description"
                    :rows="2"
                    :error="$errors->first('description')"
                >{{ old('description') }}</x-ui.forms.textarea>

                {{-- Estado Operativo (Mantenemos tu diseño de radios) --}}
                <div>
                    <x-input-label value="Estado Operativo" />
                    <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="1" class="peer hidden" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm">
                                Activo
                            </span>
                        </label>
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="0" class="peer hidden" {{ old('is_active') == '0' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-sm">
                                Inactivo
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Guardar Almacén</x-ui.button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    @foreach($warehouses as $item)

    <x-modal name="edit-warehouse-{{ $item->id }}" maxWidth="md">

        <x-form-header
            title="Editar Almacén: {{ $item->name }}"
            subtitle="Modifique la información de la ubicación."
            :back-route="route('inventory.warehouses.index')" />

        <form method="POST" action="{{ route('inventory.warehouses.update', $item) }}" class="p-6">
            @csrf @method('PUT')

            <div class="space-y-4">
                <x-ui.forms.input
                    label="Nombre del almacén"
                    name="name"
                    type="text"
                    value="{{ $item->name }}"
                    :error="$errors->first('name')"
                    required
                />

                <x-ui.forms.select
                    label="Tipo de ubicación"
                    name="type"
                    :error="$errors->first('type')"
                    required
                >
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ (old('type', $item->type) == $value) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.input
                    label="Dirección o Referencia"
                    name="address"
                    type="text"
                    value="{{ $item->address }}"
                    :error="$errors->first('address')"
                />

                <x-ui.forms.textarea
                    label="Descripción"
                    name="description"
                    :rows="2"
                    :error="$errors->first('description')"
                >{{ old('description', $item->description) }}</x-ui.forms.textarea>

                <div>
                    <x-input-label value="Estado Operativo" />
                    <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="1" class="peer hidden" {{ old('is_active', $item->is_active) == '1' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm">
                                Activo
                            </span>
                        </label>
                        <label class="flex-1">
                            <input type="radio" name="is_active" value="0" class="peer hidden" {{ old('is_active', $item->is_active) == '0' ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-sm">
                                Inactivo
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Actualizar Almacén</x-ui.button>
            </div>
        </form>
    </x-modal>

    @if($item->trashed())
        {{-- Papelera (docs/analisis/politica-soft-deletes.md §6) — borrado
             definitivo vía wireConfirm, dispara WarehouseTable::forceDelete(). --}}
        <x-ui.confirm-deletion-modal
            :id="$item->id"
            :title="'¿Eliminar Permanentemente?'"
            :itemName="$item->name"
            :type="'el almacén'"
            :wireConfirm="'forceDelete(' . $item->id . ')'"
            :description="'Estás a punto de borrar definitivamente el almacén <strong>' . e($item->name) . '</strong>.'"
        >
            <strong>Aviso Crítico:</strong> Esta operación es irreversible. Si este almacén tuvo movimientos de inventario históricos, podrías perder coherencia en reportes antiguos.
        </x-ui.confirm-deletion-modal>
    @else
        <x-ui.confirm-deletion-modal
            :id="$item->id"
            :title="'¿Eliminar Almacén?'"
            :itemName="$item->name"
            :type="'el almacén'"
            :route="route('inventory.warehouses.destroy', $item)"
        />
    @endif
    @endforeach