<x-app-layout>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Tarjeta/Contenedor del Formulario --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">

                {{-- TÍTULO MINIMALISTA AÑADIDO AQUÍ --}}
                <h2 class="text-xl font-medium text-gray-700 mb-6 border-b pb-3">{{ __('Crear Nuevo Rol') }}</h2>
                {{-- Se redujo a text-xl y font-medium, con una línea sutil de separación. --}}
                
                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    
                    {{-- Campo de Formulario --}}
                    <div class="mb-6">
                        <x-ui.forms.input
                            label="Nombre del Rol:"
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            placeholder="Ej: Administrador, Vendedor, Logística"
                            required
                            :error="$errors->first('name')"
                        />
                    </div>

                    {{-- Botones de Acción --}}
                    <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100">
                        
                        {{-- Botón Cancelar (Regresar a la lista) --}}
                        <x-ui.button href="{{ route('roles.index') }}" appearance="ghost" variant="secondary">
                            Cancelar
                        </x-ui.button>

                        {{-- Botón de Guardar --}}
                        <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                            Guardar Rol
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>