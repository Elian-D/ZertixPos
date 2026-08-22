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
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                    </span>
                    <input type="text" name="search"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Buscar por nombre de local o cliente...">
                </div>
            </form>
        </div>

        <div id="pos-trash-table">
            @include('clients.pos.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>