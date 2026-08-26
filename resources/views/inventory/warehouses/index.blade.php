<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Gestión de Almacenes" description="Gestiona los almacenes donde se distribuye tu inventario." :count="$warehouses->total()" countLabel="almacenes">
            <x-slot name="actions">

            <x-ui.button href="{{ route('inventory.warehouses.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                Papelera
            </x-ui.button>

            <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-warehouse')">
                Nuevo Almacén
            </x-ui.button>

            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('inventory.warehouses.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="warehouses-table" class="w-full overflow-hidden">
            @include('inventory.warehouses.partials.table')
        </div>
    </div>
</x-app-layout>
