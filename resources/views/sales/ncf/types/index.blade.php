{{-- resources/views/sales/ncf/types/index.blade.php --}}
<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Tipos de Comprobantes (NCF / e-NCF)">
                    <x-slot name="actions">
                        @can('manage ncf types')
                            <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'create-ncf-type')"
                                variant="primary" iconLeft="heroicon-s-plus-circle">
                                Nuevo Tipo de NCF
                            </x-ui.button>
                        @endcan
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS SIMPLIFICADOS (Solo Columnas y PerPage) --}}
                @include('sales.ncf.types.partials.filters')
                
                {{-- Contenedor de Tabla AJAX --}}
                <div id="ncf-types-table" class="w-full overflow-hidden mt-6">
                    @include('sales.ncf.types.partials.table')
                </div>
            </div>
        </div>
    </div>

</x-app-layout>