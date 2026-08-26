@include('clients.equipment.partials.filter-sources')

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Equipos" description="Gestiona el equipo instalado en las instalaciones de tus clientes." :count="$equipments->total()" countLabel="equipos">
            <x-slot name="actions">

                <x-ui.button href="{{ route('clients.equipment.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button href="{{ route('clients.equipment.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo Equipo
                </x-ui.button>
            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                    x-on:click="const form = document.getElementById('equipments-filters'); const params = form ? new URLSearchParams(new FormData(form)).toString() : ''; window.location.href = '{{ route('clients.equipment.export') }}' + (params ? '?' + params : '');"
                >
                    Exportar (Excel)
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('clients.equipment.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="equipments-table" class="w-full overflow-hidden">
            @include('clients.equipment.partials.table')
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal
        formId="equipments-filters"
        route="{{ route('clients.equipment.bulk') }}"
    />
</x-app-layout>
