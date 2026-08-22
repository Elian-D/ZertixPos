<script>
    window.filterSources = {
        ncf_types: JSON.parse('{!! addslashes(json_encode($ncf_types)) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Configuración de Secuencias NCF" description="Administra los lotes de secuencias de comprobantes fiscales autorizados por la DGII." :count="$items->total()" countLabel="lotes">
            <x-slot name="actions">
                @can('manage ncf sequences')
                    <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'create-ncf-sequence')"
                        variant="primary" iconLeft="heroicon-s-plus-circle">
                        Nuevo Lote NCF
                    </x-ui.button>
                @endcan
            </x-slot>
        </x-ui.page-header>

        {{-- Filtros del Pipeline --}}
        @include('sales.ncf.sequences.partials.filters')

        {{-- Contenedor de Tabla AJAX --}}
        <div id="ncf-sequences-table" class="w-full overflow-hidden">
            @include('sales.ncf.sequences.partials.table')
        </div>
    </div>
</x-app-layout>