<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">

            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Gestión de Categorías de Productos">
                    <x-slot name="actions">

                    <a href="{{ route('inventory.products.categories.eliminados') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                        <x-heroicon-s-trash class="w-4 h-4 mr-2" />
                        Papelera
                    </a>

                    <x-primary-button class="inline-flex items-center px-4 py-2 bg-green-600 rounded-md text-xs font-semibold text-white uppercase hover:bg-green-700" x-data x-on:click="$dispatch('open-modal', 'crear-category')">
                        <x-heroicon-s-plus class="w-5 h-5 mr-2" /> Nueva Categoría
                    </x-primary-button>
                    
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS --}}
                @include('products.categories.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="categories-table" class="w-full overflow-hidden">
                    @include('products.categories.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
