<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        {{-- TOOLBAR --}}
        <x-ui.page-header title="Papelera de Puntos de Venta" description="Consulta y restaura los puntos de venta eliminados o bórralos de forma definitiva." :count="$items->total()" countLabel="puntos de venta eliminados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('clients.delivery_points.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver a Puntos de Venta
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form id="pos-trash-filters" class="flex gap-4">
                <div class="flex-1">
                    <x-ui.forms.input
                        name="search"
                        icon-left="heroicon-s-magnifying-glass"
                        placeholder="Buscar por nombre de local o cliente..."
                    />
                </div>
            </form>
        </div>

        <div id="pos-trash-table">
            @include('clients.pos.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>