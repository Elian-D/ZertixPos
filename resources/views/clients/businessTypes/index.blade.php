<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Tipos de Negocios" description="Administra los tipos de negocio disponibles para clasificar a tus clientes." :count="$businessTypes->total()" countLabel="tipos de negocios">
            <x-slot name="actions">

                <x-ui.button href="{{ route('clients.businessTypes.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-tipoNegocio')">
                    Nuevo Tipo de Negocio
                </x-ui.button>

            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('clients.businessTypes.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="businessTypes-table" class="w-full overflow-hidden">
            @include('clients.businessTypes.partials.table')
        </div>
    </div>
</x-app-layout>
