@include('products.partials.filter-sources')

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            
            {{-- Toast Notifications --}}
            <x-ui.toasts />
            

            <div class="p-6">
                <x-page-toolbar title="Gestión de Productos/Servicios">
                    <x-slot name="actions">
                        <x-ui.button href="{{ route('inventory.products.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                            Papelera
                        </x-ui.button>

                        <x-ui.button href="{{ route('inventory.products.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                            Nuevo Producto/Servicio
                        </x-ui.button>
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS --}}
                @include('products.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="products-table" class="w-full overflow-hidden">
                    @include('products.partials.table')
                </div>
            </div>
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal 
        formId="products-filters" 
        route="{{ route('inventory.products.bulk') }}" 
    />
</x-app-layout>