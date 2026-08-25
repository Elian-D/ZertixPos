<x-app-layout>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Tarjeta/Contenedor del Formulario --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">

                {{-- TÍTULO MINIMALISTA --}}
                <h2 class="text-xl font-medium text-gray-700 mb-6 border-b pb-3">
                    {{ __('Editar usuario: ') }} <span class="text-indigo-600 font-semibold">{{ $user->name }}</span>
                </h2>
                
                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- 1. CAMPO: Nombre --}}
                    <div class="mb-6">
                        <x-ui.forms.input
                            label="Nombre del Usuario:"
                            type="text"
                            name="name"
                            id="name"
                            value="{{ $user->name }}"
                            placeholder="Ej: Juan Pérez"
                            :error="$errors->first('name')"
                        />
                    </div>

                    {{-- 2. CAMPO: Email --}}
                    <div class="mb-6">
                        <x-ui.forms.input
                            label="Correo Electrónico:"
                            type="email"
                            name="email"
                            id="email"
                            value="{{ $user->email }}"
                            placeholder="ejemplo@dominio.com"
                            :error="$errors->first('email')"
                        />
                    </div>

                {{-- CONTENEDOR PRINCIPAL para el estado 'show' compartido --}}
                    {{-- Password y Password Confirmation se dejan con <input> nativo: el botón
                         de mostrar/ocultar contraseña (ícono clickeable superpuesto que cambia
                         `:type`) no encaja en x-ui.forms.input — iconRight solo acepta un
                         heroicon estático sin @click, no un botón interactivo. --}}
                    <div x-data="{ show: false }">
                        
                        {{-- 3. CAMPO: Contraseña (Con el botón de control) --}}
                        <div class="mb-6"> 
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña (Mín. 8 caracteres):</label>
                            
                            <div class="relative">
                                {{-- INPUT: Referencia el estado 'show' del padre --}}
                                <input :type="show ? 'text' : 'password'"
                                        name="password" 
                                        id="password" 
                                        placeholder="Escribe una contraseña segura"
                                        minlength="8"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-base py-2.5 pl-4 pr-12
                                                focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50
                                                @error('password') border-red-500 focus:border-red-500 focus:ring-red-200 @enderror">
                                
                                {{-- Botón para mostrar/ocultar la contraseña (ÚNICO BOTÓN) --}}
                                <button type="button" 
                                        @click="show = !show" {{-- Alterna el estado 'show' del padre --}}
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                        title="Mostrar/Ocultar Contraseña">
                                    
                                    <template x-if="!show">
                                        {{-- Ícono de Ojo Cerrado (Contraseña oculta) --}}
                                        <x-heroicon-s-eye-slash class="w-5 h-5" />
                                    </template>

                                    <template x-if="show">
                                        {{-- Ícono de Ojo Abierto (Contraseña visible) --}}
                                        <x-heroicon-s-eye class="w-5 h-5" />
                                    </template>
                                </button>
                            </div>

                            @error('password') 
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p> 
                            @enderror
                        </div>

                        {{-- 4. CAMPO: Confirmar Contraseña (Sin botón de control) --}}
                        <div class="mb-6"> 
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña:</label>
                            
                            <div class="relative">
                                {{-- INPUT: Referencia el mismo estado 'show' del padre --}}
                                <input :type="show ? 'text' : 'password'"
                                        name="password_confirmation" 
                                        id="password_confirmation" 
                                        placeholder="Repite la contraseña"
                                        minlength="8"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-base py-2.5 pl-4 pr-12
                                                focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50
                                                @error('password') border-red-500 focus:border-red-500 focus:ring-red-200 @enderror">
                                
                                {{-- ELIMINAMOS EL BOTÓN Y EL @error('password_confirmation') DE AQUÍ --}}
                            </div>

                            {{-- El error de confirmación se sigue mostrando aquí, usando @error('password') --}}
                            @error('password') 
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p> 
                            @enderror
                        </div>
                    </div> 
                    {{-- FIN del CONTENEDOR PRINCIPAL (show compartido) --}}

                    {{-- Botones de Acción (Sin cambios) --}}
                    <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100">
                        
                        {{-- Botón Cancelar (Regresar a la lista) --}}
                        <x-ui.button href="{{ route('users.index') }}" appearance="ghost" variant="secondary">
                            Cancelar
                        </x-ui.button>

                        {{-- Botón de Guardar --}}
                        <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-user-plus">
                            Actualizar Usuario
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>