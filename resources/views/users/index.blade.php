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
                <h2 class="text-xl font-medium text-gray-700 mb-6 border-b pb-3">{{ __('Gestión de Usuarios') }}</h2>

                {{-- 2. BARRA DE HERRAMIENTAS (Búsqueda y Creación) --}}
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                    
                    {{-- Formulario de Búsqueda Estilizado --}}
                    <form action="{{ route('users.index') }}" method="GET" class="w-full md:w-1/3">
                        <x-ui.forms.input
                            type="text"
                            name="search"
                            placeholder="Buscar usuarios..."
                            value="{{ $search ?? '' }}"
                            icon-left="heroicon-s-magnifying-glass"
                        />
                    </form>

                    {{-- Botón Crear Usuario — deshabilitado (no oculto) al llegar al límite del
                         plan (REQ-05.6): el dueño necesita saber POR QUÉ no puede, no que el
                         botón simplemente desaparezca. --}}
                    <div class="flex flex-col items-end gap-1">
                        @if ($canCreateMoreUsers)
                            <x-ui.button href="{{ route('users.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                                {{ __('Crear Nuevo Usuario') }}
                            </x-ui.button>
                            @if (! is_null($usersLimit))
                                <p class="text-xs text-gray-400">
                                    {{ $totalUsersCount }}/{{ $usersLimit }} usuarios utilizados
                                </p>
                            @endif
                        @else
                            <x-ui.button type="button" disabled variant="primary" iconLeft="heroicon-s-plus"
                                title="Tu plan actual permite un máximo de {{ $usersLimit }} usuario(s). Actualizá tu plan para agregar más.">
                                {{ __('Crear Nuevo Usuario') }}
                            </x-ui.button>
                            <p class="text-xs text-amber-600 font-medium">
                                Límite del plan alcanzado ({{ $totalUsersCount }}/{{ $usersLimit }} usuarios) — actualizá tu plan para agregar más.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- 3. TABLA ESTILIZADA Y RESPONSIVE (USANDO COMPONENTE) --}}
                {{-- La magia: El componente solo provee la estructura <table>/<thead>/<tbody> --}}
                <x-data-table :items="$users" :headers="['ID', 'Avatar' , 'Nombre y Email', 'Rol', 'Creado', 'Actualizado']">
                    
                    {{-- El ciclo va AQUÍ, y el resultado de cada iteración (el <tr> completo) es el contenido del $slot --}}
                    @forelse($users as $user)
                        <tr class="block md:table-row hover:bg-gray-50 transition duration-150 p-4 border-b border-gray-200 md:border-b-0">
                            
                            {{-- Columna 1: ID --}}
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-1/12">{{ $user->id }}</td>
                            {{-- Columna 2: Avatar --}}
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-1/12 ">
                                    <div class="w-9 h-9 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center border-2 border-transparent hover:border-indigo-400 transition-colors duration-200">
                                        
                                        {{-- Lógica para AVATAR DE INICIALES --}}
                                        @if ( $user->avatar_url)
                                            <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-sm font-semibold text-gray-600">
                                                {{ $user->getInitials() }}
                                            </span>
                                        @endif
                                        
                                    </div>
                            </td>
                            
                            {{-- Columna 3: Nombre y Email (Cuerpo de la Card en móvil) --}}
                            <td class="block md:table-cell px-6 py-4  text-sm text-gray-600 w-full md:w-3/12">
                                <div class="font-bold text-gray-900 text-base mb-1 md:font-normal md:text-sm">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500 md:text-gray-600">{{ $user->email }}</div>
                            </td>
                            
                            {{-- Columna 4: Rol (Oculto en lg-) --}}
                            <td class="hidden md:table-cell px-6 py-4  text-sm text-gray-600 w-2/12">{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>

                            
                            {{-- Columna 5: Creado (Oculto en lg-) --}}
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-600 w-1/12">{{ $user->created_at->format('d/m/Y') }}</td>
                            
                            {{-- Columna 6: Actualizado (Oculto en lg-) --}}
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-600 w-1/12">{{ $user->updated_at->format('d/m/Y') }}</td>

                            {{-- CELDA DE ACCIONES --}}
                            <td class="block md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium w-full md:w-auto">
                                <div class="flex items-center space-x-2">
                                    {{-- Botón Editar --}}
                                    <a href="{{ route('users.edit', $user) }}" title="Editar Usuario" class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-100"><x-heroicon-s-pencil class="w-5 h-5" /></a>
                                    
                                    {{-- Botón Roles --}}
                                    <a href="{{ route('users.roles.edit', $user) }}" title="Asignar Roles y Permisos" class="text-teal-600 hover:text-teal-900 p-1 rounded-md hover:bg-teal-100"><x-heroicon-s-key class="w-5 h-5" /></a>

                                    {{-- Botón Eliminar (Disparador del Modal) --}}
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" x-data>
                                        @csrf @method('DELETE')
                                        <button type="button" @click="$dispatch('open-modal', 'confirm-user-deletion-{{ $user->id }}')" 
                                            title="Eliminar Usuario"
                                            class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-100">
                                            <x-heroicon-s-trash class="w-5 h-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                         {{-- Si no hay usuarios, agregamos una fila de "No hay datos" --}}
                         <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 text-sm">No se encontraron usuarios.</td>
                        </tr>
                    @endforelse

                </x-data-table>
            </div>
        </div>
    </div>

{{-- MODAL DE CONFIRMACIÓN (AJUSTADO PARA USUARIOS) --}}
@foreach($users as $user)
    {{-- Asegúrate de que $errors->userDeletion->isNotEmpty() esté disponible en caso de error --}}
    <x-modal name="confirm-user-deletion-{{ $user->id }}" :show="false" maxWidth="md">
        <form method="post" action="{{ route('users.destroy', $user) }}" class="p-6">
            @csrf
            @method('delete')

            {{-- Título y Mensaje de Advertencia --}}
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('¿Estás seguro de que quieres eliminar a este usuario?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Esta acción es irreversible. Estás a punto de eliminar al usuario: ') }}
                <span class="font-bold text-red-600">{{ $user->name }}</span>.
            </p>

            {{-- Área de Botones del Modal --}}
            <div class="mt-6 flex justify-end">
                
                {{-- Botón Cancelar --}}
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-ui.button>

                {{-- Botón Eliminar (Rojo) --}}
                <x-ui.button type="submit" variant="error" iconLeft="heroicon-s-trash" class="ms-3">
                    {{ __('Eliminar Usuario') }}
                </x-ui.button>
            </div>
        </form>
    </x-modal>
@endforeach
</x-app-layout>