<script>
    window.filterSources = {
        warehouses: JSON.parse('{!! addslashes(json_encode($warehouses->pluck("name", "id"))) !!}'),
        categories: JSON.parse('{!! addslashes(json_encode($categories->pluck("name", "id"))) !!}'),
    };
</script>
<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Estado Actual de Inventario" description="Consulta las existencias actuales de tus productos por almacén." :count="$stocks->total()" countLabel="existencias">
            <x-slot:secondary>
                <x-ui.button href="{{ route('inventory.stocks.export', request()->query()) }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-down-tray" class="w-full justify-start">
                    Exportar Inventario
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('inventory.stocks.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="stocks-table" class="w-full overflow-hidden">
            @include('inventory.stocks.partials.table')
        </div>
    </div>
</x-app-layout>