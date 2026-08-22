<script>
    window.filterSources = {
        warehouses: JSON.parse('{!! addslashes(json_encode($warehouses->pluck("name", "id"))) !!}'),
        products: JSON.parse('{!! addslashes(json_encode($products->pluck("name", "id"))) !!}'),
        movementTypes: JSON.parse('{!! addslashes(json_encode($types)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Kardex de Inventario" description="Consulta el historial de movimientos de inventario y registra ajustes manuales de stock." :count="$items->total()" countLabel="movimientos">
            <x-slot name="actions">
                {{-- Botón para abrir el Modal de Ajuste Manual --}}
                <x-ui.button variant="primary" iconLeft="heroicon-s-adjustments-vertical" x-data x-on:click="$dispatch('open-modal', 'create-adjustment')">
                    Ajuste de Stock
                </x-ui.button>
            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary"
                    appearance="ghost"
                    size="sm"
                    class="w-full justify-start"
                    iconLeft="heroicon-s-arrow-down-tray"
                    x-data
                    x-on:click="
                        const form = document.getElementById('movements-filters');
                        const params = form ? new URLSearchParams(new FormData(form)).toString() : '';
                        window.location.href = '{{ route('inventory.movements.export') }}' + (params ? '?' + params : '');
                    "
                >
                    Exportar
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- Filtros del Pipeline --}}
        @include('inventory.movements.partials.filters')

        {{-- Tabla AJAX --}}
        <div id="movements-table" class="w-full overflow-hidden">
            @include('inventory.movements.partials.table')
        </div>
    </div>

    {{-- Modal para registrar ajustes manuales --}}
    @include('inventory.movements.partials.modals')
</x-app-layout>