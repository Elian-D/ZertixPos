    @include('clients.partials.filter-sources')
<x-app-layout>

    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            
            <x-ui.toasts />

            <div class="p-6">
                
            <x-page-toolbar title="Gestión de Clientes">
                <x-slot name="actions">

                    <x-ui.button href="{{ route('clients.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                        Papelera
                    </x-ui.button>

                    <x-ui.button href="{{ route('clients.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                        Nuevo Cliente
                    </x-ui.button>

                    <x-data-table.export-button 
                        :route="route('clients.export')" 
                        formId="clients-filters" 
                    />

                    <x-data-table.import-link 
                        :route="route('clients.import.view')" 
                        title="Importar clientes"
                    />

                </x-slot>
            </x-page-toolbar>


                {{-- FILTROS Y BARRA DE BÚSQUEDA --}}
                @include('clients.partials.filters')

                {{-- TABLA --}}
                <div id="clients-table" class="w-full overflow-hidden">
                    @include('clients.partials.table')
                </div>
            </div>
        </div>
    </div>

<x-data-table.bulk-confirmation-modal 
    formId="clients-filters" 
    route="{{ route('clients.bulk') }}" 
/>


</x-app-layout>