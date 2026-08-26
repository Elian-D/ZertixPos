<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        {{-- TOOLBAR --}}
        <x-ui.page-header title="Papelera de Productos" description="Consulta y restaura los productos eliminados o bórralos de forma definitiva." :count="$items->total()" countLabel="productos eliminados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('inventory.products.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al Catálogo
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form id="product-trash-filters" class="flex gap-4">
                <div class="flex-1">
                    <x-ui.forms.input
                        name="search"
                        icon-left="heroicon-s-magnifying-glass"
                        placeholder="Buscar por nombre, SKU o categoría..."
                    />
                </div>
            </form>
        </div>

        <div id="product-trash-table">
            @include('products.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>