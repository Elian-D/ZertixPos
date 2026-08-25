<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        {{-- TOOLBAR --}}
        <x-ui.page-header title="Papelera de Tipos de Negocios" description="Consulta y restaura los tipos de negocio eliminados o bórralos de forma definitiva." :count="$items->total()" countLabel="tipos de negocios eliminados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('clients.businessTypes.index') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver a Tipos de Negocios
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
            <form id="businessTypes-trash-filters" class="flex gap-4">
                <div class="relative flex-1">
                    <x-ui.forms.input
                        type="text"
                        name="search"
                        icon-left="heroicon-s-magnifying-glass"
                        placeholder="Buscar nombre o prefijo..."
                    />
                </div>
            </form>
        </div>

        <div>
            @include('clients.businessTypes.partials.eliminados-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>