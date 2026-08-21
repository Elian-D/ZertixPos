<script>
    window.filterSources = {
        warehouses: JSON.parse('{!! addslashes(json_encode($warehouses->pluck("name", "id"))) !!}'),
        products: JSON.parse('{!! addslashes(json_encode($products->pluck("name", "id"))) !!}'),
        movementTypes: JSON.parse('{!! addslashes(json_encode($types)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Kardex de Inventario">
                    <x-slot name="actions">
                        {{-- Botón para abrir el Modal de Ajuste Manual --}}
                        <x-ui.button variant="primary" iconLeft="heroicon-s-adjustments-vertical" x-data x-on:click="$dispatch('open-modal', 'create-adjustment')">
                            Ajuste de Stock
                        </x-ui.button>

                        <x-data-table.export-button :route="route('inventory.movements.export')" formId="movements-filters" />
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Pipeline --}}
                @include('inventory.movements.partials.filters')

                {{-- Tabla AJAX --}}
                <div id="movements-table" class="w-full overflow-hidden">
                    @include('inventory.movements.partials.table')
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para registrar ajustes manuales --}}
    @include('inventory.movements.partials.modals')
</x-app-layout>