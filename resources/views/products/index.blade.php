@include('products.partials.filter-sources')

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Productos/Servicios" description="Gestiona el catálogo de productos y servicios, sus precios e inventario." :count="$products->total()" countLabel="productos">
            <x-slot name="actions">
                <x-ui.button href="{{ route('inventory.products.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button href="{{ route('inventory.products.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo Producto/Servicio
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('products.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="products-table" class="w-full overflow-hidden">
            @include('products.partials.table')
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal
        formId="products-filters"
        route="{{ route('inventory.products.bulk') }}"
    />
</x-app-layout>