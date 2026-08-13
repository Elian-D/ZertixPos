

<x-app-layout>
    <x-ui.toasts />
    <x-data-table.import.main-container 
        title="Clientes" 
        uploadRoute="clients.import.process"
        templateRoute="{{ route('clients.template') }}"
    >
        <x-slot:catalogs>
            <x-data-table.import.catalog-link 
                :route="route('catalog.states')" 
                label="Lista de Provincias permitidas" 
            />
            <x-data-table.import.catalog-link
                :route="route('catalog.tax-types')"
                label="Lista de Tipos de Identificación"
            />
        </x-slot:catalogs>
    </x-data-table.import.main-container>

</x-app-layout>