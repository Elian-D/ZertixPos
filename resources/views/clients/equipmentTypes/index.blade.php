<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Tipos Equipos" description="Administra los tipos de equipo disponibles para clasificar el parque instalado." :count="$equipmentsTypes->total()" countLabel="tipos de equipos">
            <x-slot name="actions">

                <x-ui.button href="{{ route('clients.equipmentTypes.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-tipoEquipo')">
                    Nuevo Tipo de Equipo
                </x-ui.button>

            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('clients.equipmentTypes.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="equipmentsTypes-table" class="w-full overflow-hidden">
            @include('clients.equipmentTypes.partials.table')
        </div>
    </div>
</x-app-layout>
