<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Papelera de Unidades de medidas" description="Consulta y restaura las unidades de medida eliminadas recientemente." :count="$items->total()" countLabel="unidades">
            <x-slot name="actions">
                <x-ui.button href="{{ route('inventory.products.units.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver a Unidad de medidas
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div>
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                <form id="equipment-trash-filters" class="flex gap-4">
                    <div class="flex-1">
                        <x-ui.forms.input
                            name="search"
                            icon-left="heroicon-s-magnifying-glass"
                            placeholder="Buscar nombre o abreviatura..."
                        />
                    </div>
                </form>
            </div>

            <div>
                @include('products.units.partials.eliminados-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>