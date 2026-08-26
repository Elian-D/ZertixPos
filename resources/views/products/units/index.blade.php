<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Gestión de Unidades de Medidas" description="Gestiona las unidades de medida utilizadas para tus productos e inventario." :count="$units->total()" countLabel="unidades">
            <x-slot name="actions">

            <x-ui.button href="{{ route('inventory.products.units.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                Papelera
            </x-ui.button>

            <x-ui.button variant="primary" iconLeft="heroicon-s-plus" x-data x-on:click="$dispatch('open-modal', 'crear-unit')">
                Nueva Unidad de Medida
            </x-ui.button>

            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('products.units.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="units-table" class="w-full overflow-hidden">
            @include('products.units.partials.table')
        </div>
    </div>
</x-app-layout>
