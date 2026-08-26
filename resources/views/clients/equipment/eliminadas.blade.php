<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        {{-- TOOLBAR --}}
        <x-ui.page-header title="Papelera de Equipos" description="Consulta y restaura los equipos eliminados o bórralos de forma definitiva." :count="$items->total()" countLabel="equipos eliminados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('clients.equipment.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver a Equipos
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
            <form id="equipment-trash-filters" class="flex gap-4">
                <div class="flex-1">
                    <x-ui.forms.input
                        name="search"
                        icon-left="heroicon-s-magnifying-glass"
                        placeholder="Buscar por código, nombre o modelo..."
                    />
                </div>
            </form>
        </div>

        <div id="equipment-trash-table">
            @include('clients.equipment.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>