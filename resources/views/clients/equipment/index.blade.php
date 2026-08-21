@include('clients.equipment.partials.filter-sources')

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">

            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Gestión de Equipos">
                    <x-slot name="actions">

                    <x-ui.button href="{{ route('clients.equipment.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                        Papelera
                    </x-ui.button>

                        <x-ui.button href="{{ route('clients.equipment.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                            Nuevo Equipo
                        </x-ui.button>

                        <x-data-table.export-button 
                            :route="route('clients.equipment.export')" 
                            formId="equipments-filters" 
                        />
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS --}}
                @include('clients.equipment.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="equipments-table" class="w-full overflow-hidden">
                    @include('clients.equipment.partials.table')
                </div>
            </div>
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal 
        formId="equipments-filters" 
        route="{{ route('clients.equipment.bulk') }}" 
    />
</x-app-layout>
