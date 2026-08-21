<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">

        <x-ui.toasts />

        {{-- TOOLBAR --}}
        <x-page-toolbar title="Papelera de Productos" subtitle="Artículos eliminados que pueden ser restaurados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('inventory.products.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al Catálogo
                </x-ui.button>
            </x-slot>
        </x-page-toolbar>

        {{-- CONTENEDOR DE TABLA Y FILTROS --}}
        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100 mt-6 p-6">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                <form id="product-trash-filters" class="flex gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </span>
                        <input type="text" name="search" 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                placeholder="Buscar por nombre, SKU o categoría...">
                    </div>
                </form>
            </div>

            <div id="product-trash-table">
                @include('products.partials.eliminados-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>