<script>
    window.filterSources = {
        warehouses: JSON.parse('{!! addslashes(json_encode($warehouses->pluck("name", "id"))) !!}'),
        categories: JSON.parse('{!! addslashes(json_encode($categories->pluck("name", "id"))) !!}'),
    };
</script>
<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Estado Actual de Inventario">
                    <x-slot name="actions">
                        <x-ui.button href="{{ route('inventory.stocks.export', request()->query()) }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-down-tray">
                            Exportar Inventario
                        </x-ui.button>
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS --}}
                @include('inventory.stocks.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="stocks-table" class="w-full overflow-hidden mt-4">
                    @include('inventory.stocks.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>