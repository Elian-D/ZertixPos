<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Papelera de Clientes" description="Consulta y restaura los clientes eliminados o bórralos de forma definitiva." :count="$items->total()" countLabel="clientes eliminados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('clients.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al listado
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form id="clients-trash-filters" class="flex gap-4">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                    </span>
                    <input type="text" name="search"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Buscar por nombre, RNC o email...">
                </div>
            </form>
        </div>

        {{-- Contenedor de la Tabla --}}
        <div id="clients-trash-table" class="p-0">
            @include('clients.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>
