@include('clients.partials.filter-sources')
<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Clientes" description="Gestiona la cartera de clientes, sus datos de contacto y condiciones comerciales." :count="$clients->total()" countLabel="clientes">
            <x-slot name="actions">

                <x-ui.button href="{{ route('clients.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button href="{{ route('clients.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo Cliente
                </x-ui.button>

            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                    x-on:click="const form = document.getElementById('clients-filters'); const params = form ? new URLSearchParams(new FormData(form)).toString() : ''; window.location.href = '{{ route('clients.export') }}' + (params ? '?' + params : '');"
                >
                    Exportar (Excel)
                </x-ui.button>

                <x-ui.button href="{{ route('clients.import.view') }}" variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-up-tray">
                    Importar clientes
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- FILTROS Y BARRA DE BÚSQUEDA --}}
        @include('clients.partials.filters')

        {{-- TABLA --}}
        <div id="clients-table" class="w-full overflow-hidden">
            @include('clients.partials.table')
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal 
        formId="clients-filters" 
        route="{{ route('clients.bulk') }}" 
    />


</x-app-layout>