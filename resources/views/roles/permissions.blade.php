<x-app-layout>
    
    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                
                <h2 class="text-xl font-medium text-gray-700 mb-6 border-b pb-3">
                    {{ __('Asignar Permisos al rol: ') }} <span class="text-indigo-600 font-semibold">{{ $role->name }}</span>
                </h2>

                {{-- Mensaje de Sesión --}}
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6">
                        <span class="block sm:inline font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('roles.permissions.update', $role) }}" method="POST" x-data="{}">
                    @csrf
                    
                    {{-- 2. BARRA DE ACCIONES MINIMALISTA --}}
                    <div class="flex justify-end mb-6">
                        <div class="flex items-center space-x-3 text-sm font-medium">
                            
                            {{-- Botón Seleccionar Todos --}}
                            <button type="button" 
                                    @click="$nextTick(() => { document.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = true) })"
                                    class="text-indigo-600 hover:text-indigo-800 transition duration-150 flex items-center">
                                <x-heroicon-s-check-circle class="w-4 h-4 mr-1" />
                                Seleccionar Todos
                            </button>

                            <span class="text-gray-300">|</span>

                            {{-- Botón Quitar Todos --}}
                            <button type="button" 
                                    @click="$nextTick(() => { document.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = false) })"
                                    class="text-red-600 hover:text-red-800 transition duration-150 flex items-center">
                                <x-heroicon-s-x-circle class="w-4 h-4 mr-1" />
                                Quitar Todos
                            </button>
                        </div>
                    </div>
                    
                    {{-- 3. PERMISOS AGRUPADOS (Estructura de Tarjeta Limpia) --}}
                    <div class="space-y-6">
                        @foreach($groupedPermissions as $groupName => $perms)
                            {{-- Contenedor de Grupo Estilizado --}}
                            <div class="border border-gray-100 rounded-lg p-4 shadow-sm">
                                
                                {{-- Título de la División --}}
                                <h3 class="text-lg font-semibold text-gray-800 capitalize mb-4 pb-2 border-b border-gray-100">
                                    Permisos {{ str_replace(['-', '_'], ' ', $groupName) }}
                                </h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3">
                                    @foreach($perms as $permission)
                                        <x-ui.forms.checkbox
                                            label="{{ ucfirst(explode('.', $permission->name)[1] ?? $permission->name) }}"
                                            name="permissions[]"
                                            id="perm_{{ $permission->id }}"
                                            value="{{ $permission->name }}"
                                            :checked="in_array($permission->name, $rolePermissions)"
                                        />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>


                    {{-- 4. BOTONES DE GUARDAR Y CANCELAR --}}
                    <div class="flex justify-end space-x-4 pt-6 mt-6 border-t border-gray-100">
                        
                         {{-- Botón Cancelar (Minimalista) --}}
                        <a href="{{ route('roles.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            Cancelar
                        </a>
                        
                        {{-- Botón de Guardar --}}
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            <x-heroicon-s-check class="w-5 h-5 mr-2 -ml-1" />
                            Guardar Permisos
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>