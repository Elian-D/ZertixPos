{{-- resources/views/sales/ncf/types/index.blade.php --}}
<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Tipos de Comprobantes (NCF / e-NCF)" description="Configura los tipos de comprobantes fiscales disponibles para la facturación." :count="$items->total()" countLabel="tipos">
            <x-slot name="actions">
                @can('ncf_types.manage')
                    <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'create-ncf-type')"
                        variant="primary" iconLeft="heroicon-s-plus-circle">
                        Nuevo Tipo de NCF
                    </x-ui.button>
                @endcan
            </x-slot>
        </x-ui.page-header>

        {{-- FILTROS SIMPLIFICADOS (Solo Columnas y PerPage) --}}
        @include('sales.ncf.types.partials.filters')

        {{-- Contenedor de Tabla AJAX --}}
        <div id="ncf-types-table" class="w-full overflow-hidden">
            @include('sales.ncf.types.partials.table')
        </div>
    </div>

</x-app-layout>