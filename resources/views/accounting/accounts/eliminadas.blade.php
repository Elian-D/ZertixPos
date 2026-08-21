<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">

        <x-ui.toasts />

        {{-- TOOLBAR --}}
        <x-page-toolbar title="Papelera de Cuentas" subtitle="Cuentas contables eliminadas recientemente">
            <x-slot name="actions">
                <x-ui.button href="{{ route('finance.accounts.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver al Catálogo
                </x-ui.button>
            </x-slot>
        </x-page-toolbar>

        {{-- CONTENEDOR --}}
        <div class="bg-white shadow-xl rounded-xl border border-gray-100 mt-6 p-6">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                <form id="accounts-trash-filters" class="flex gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </span>
                        <input type="text" name="search" 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:ring-red-500 sm:text-sm shadow-sm" 
                                placeholder="Buscar cuenta por código o nombre...">
                    </div>
                </form>
            </div>

            <div class="mt-4">
                @include('accounting.accounts.partials.eliminados-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>