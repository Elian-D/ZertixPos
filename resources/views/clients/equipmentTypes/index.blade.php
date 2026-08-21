<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">

            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Gestión de Tipos Equipos">
                    <x-slot name="actions">

                    <x-ui.button href="{{ route('clients.equipmentTypes.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                        Papelera
                    </x-ui.button>

                    <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-tipoEquipo')">
                        Nuevo Tipo de Equipo
                    </x-ui.button>
                    
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS --}}
                @include('clients.equipmentTypes.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="equipmentsTypes-table" class="w-full overflow-hidden">
                    @include('clients.equipmentTypes.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
