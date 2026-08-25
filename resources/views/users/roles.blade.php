<x-app-layout>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Tarjeta/Contenedor del Formulario --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">

                {{-- TÍTULO MINIMALISTA --}}
                <h2 class="text-xl font-medium text-gray-700 mb-6 border-b pb-3">
                    <div class="flex items-center space-x-2">
                        <span>{{ __('Asignar Rol a: ') }} <span class="text-indigo-600 font-semibold">{{ $user->name }}</span></span>
                    </div>
                </h2>
                
                {{-- FORMULARIO --}}
                <form action="{{ route('users.roles.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- INFORMACIÓN DEL USUARIO (Contexto) --}}
                    <div class="mb-8 border p-4 rounded-lg bg-gray-50 text-sm text-gray-600">
                        <p class="font-semibold text-gray-700 mb-1">Usuario:</p>
                        <p>Nombre: <span class="font-medium">{{ $user->name }}</span></p>
                        <p>Email: <span class="font-medium">{{ $user->email }}</span></p>
                        <p class="mt-2">Rol Actual: 
                            <span class="font-medium text-teal-600">
                                {{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol asignado' }}
                            </span>
                        </p>
                    </div>

                    {{-- 1. CAMPO: ASIGNACIÓN DE ROL --}}
                    <div class="mb-6">
                        <x-ui.forms.select
                            label="Seleccione el Nuevo Rol:"
                            name="role_id"
                            id="role_id"
                            required
                            placeholder="-- Elija un Rol --"
                            :error="$errors->first('role_id')"
                        >
                            @foreach($roles as $role)
                                @php
                                    // Verifica si el ID del rol actual está en el array $userRoles
                                    $isSelected = in_array($role->id, $userRoles);
                                @endphp

                                <option
                                    value="{{ $role->id }}"
                                    {{ $isSelected ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    {{-- Botones de Acción --}}
                    <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100 mt-8">
                        
                        {{-- Botón Cancelar (Regresar a la lista) --}}
                        <a href="{{ route('users.index') }}" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            Cancelar
                        </a>
                        
                        {{-- Botón de Guardar (Aseguramos que el contenido esté presente) --}}
                        <button type="submit"
                                 class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            <x-heroicon-s-key class="w-5 h-5 mr-2 -ml-1" />
                            Guardar Rol
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>