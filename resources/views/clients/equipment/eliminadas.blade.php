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
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                    </span>
                    <input type="text" name="search"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:ring-indigo-500 sm:text-sm shadow-sm"
                            placeholder="Buscar por código, nombre o modelo...">
                </div>
            </form>
        </div>

        <div id="equipment-trash-table">
            @include('clients.equipment.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>