<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Papelera de CxC" description="Consulta y restaura las cuentas por cobrar eliminadas o anuladas recientemente." :count="$items->total()" countLabel="cuentas">
            <x-slot name="actions">
                <x-ui.button href="{{ route('finance.receivables.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al Listado
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div>
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                <form id="receivables-trash-filters" class="flex gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </span>
                        <input type="text" name="search"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:ring-red-500 sm:text-sm shadow-sm"
                                placeholder="Buscar por número de documento o cliente...">
                    </div>
                </form>
            </div>

            <div class="mt-4">
                @include('accounting.receivables.partials.eliminados-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>