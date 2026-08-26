<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Papelera de Clientes" description="Consulta y restaura los clientes eliminados o bórralos de forma definitiva." :count="$items->total()" countLabel="clientes eliminados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('clients.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al listado
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

            <form id="clients-trash-filters" class="flex gap-4">
                <div class="relative flex-1">
                    <x-ui.forms.input
                        type="text"
                        name="search"
                        icon-left="heroicon-s-magnifying-glass"
                        placeholder="Buscar por nombre, RNC o email..."
                    />
                </div>
            </form>

        {{-- Contenedor de la Tabla --}}
        <div id="clients-trash-table" class="p-0">
            @include('clients.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>
