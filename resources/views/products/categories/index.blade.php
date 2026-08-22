<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Categorías de Productos" description="Administra las categorías utilizadas para organizar el catálogo de productos." :count="$categories->total()" countLabel="categorías">
            <x-slot name="actions">

                <x-ui.button href="{{ route('inventory.products.categories.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-category')">
                    Nueva Categoría
                </x-ui.button>

            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('products.categories.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="categories-table" class="w-full overflow-hidden">
            @include('products.categories.partials.table')
        </div>
    </div>
</x-app-layout>
