<x-app-layout>
    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                {{-- 1. MENSAJE DE SESIÓN --}}
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                        <span class="block sm:inline font-medium">{{ session('success') }}</span>
                    </div>
                @endif
                
                {{-- TÍTULO MINIMALISTA --}}
                <h2 class="text-xl font-medium text-gray-700 mb-6 border-b pb-3">{{ __('Gestión de Roles') }}</h2>

                {{-- 2. BARRA DE HERRAMIENTAS (Búsqueda y Creación) --}}
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                    
                    {{-- Formulario de Búsqueda Estilizado --}}
                    <form action="{{ route('config.roles.index') }}" method="GET" class="w-full md:w-1/3">
                        <x-ui.forms.input
                            type="text"
                            name="search"
                            placeholder="Buscar roles..."
                            value="{{ $search ?? '' }}"
                            icon-left="heroicon-s-magnifying-glass"
                        />
                    </form>

                    {{-- Botón Estilizado para Crear Rol --}}
                    <x-ui.button href="{{ route('config.roles.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                        {{ __('Crear Nuevo Rol') }}
                    </x-ui.button>
                </div>

                {{-- 3. TABLA ESTILIZADA --}}
                <x-data-table :items="$roles" :headers="['ID', 'Nombre', 'Creado', 'Actualizado']"> 
                    @forelse($roles as $role)
                        <tr class="block md:table-row hover:bg-gray-50 transition duration-150 p-4 border-b border-gray-200 md:border-b-0">
                            
                            {{-- Columna 1: ID --}}
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-1/12">{{ $role->id }}</td>
                            
                            {{-- Columna 2: Nombre (En móvil, es el cuerpo de la tarjeta) --}}
                            <td class="block md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-600 w-full md:w-4/12 font-bold text-gray-900 md:font-normal">{{ $role->name }}</td>
                            
                            {{-- Columna 3: Creado (Oculto en lg-) --}}
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-600 w-1/12">{{ $role->created_at->format('d/m/Y') }}</td>
                            
                            {{-- Columna 4: Actualizado (Oculto en lg-) --}}
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-600 w-1/12">{{ $role->updated_at->format('d/m/Y') }}</td>

                            {{-- CELDA DE ACCIONES --}}
                            <td class="block md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium w-full md:w-auto">
                                <div class="flex items-center space-x-2">
                                    {{-- Botón Editar --}}
                                    <a href="{{ route('config.roles.edit', $role) }}" title="Editar Rol" class="text-zertix-primary-600 hover:text-zertix-primary-900 p-1 rounded-md hover:bg-zertix-primary-100"><x-heroicon-s-pencil class="w-5 h-5" /></a>
                                    
                                    {{-- Botón Permisos --}}
                                    <a href="{{ route('config.roles.permissions.edit', $role) }}" title="Asignar Permisos" class="text-teal-600 hover:text-teal-900 p-1 rounded-md hover:bg-teal-100"><x-heroicon-s-key class="w-5 h-5" /></a>

                                    {{-- Botón Eliminar (Disparador del Modal) --}}
                                    <form action="{{ route('config.roles.destroy', $role) }}" method="POST" class="inline-block" x-data>
                                        @csrf @method('DELETE')
                                        <button type="button" @click="$dispatch('open-modal', 'confirm-role-deletion-{{ $role->id }}')" 
                                            title="Eliminar Rol"
                                            class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-100">
                                            <x-heroicon-s-trash class="w-5 h-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- COLSPAN CORREGIDO A 5 --}}
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 text-sm">No se encontraron roles.</td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>
        </div>
    </div>
    
{{-- MODALES --}}
@foreach($roles as $role)
    <x-modal name="confirm-role-deletion-{{ $role->id }}" :show="$errors->roleDeletion->isNotEmpty()" maxWidth="md">
        <form method="post" action="{{ route('config.roles.destroy', $role) }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('¿Estás seguro de que quieres eliminar este rol?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Esta acción es irreversible. Estás a punto de eliminar el rol: ') }}
                <span class="font-bold text-red-600">{{ $role->name }}</span>.
                {{ __('Asegúrate de que no hay usuarios asignados a este rol antes de proceder.') }}
            </p>

            <div class="mt-6 flex justify-end">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="error" iconLeft="heroicon-s-trash" class="ms-3">
                    {{ __('Eliminar Rol') }}
                </x-ui.button>
            </div>
        </form>
    </x-modal>
@endforeach
</x-app-layout>