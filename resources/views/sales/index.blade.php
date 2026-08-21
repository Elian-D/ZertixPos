<script>
    window.filterSources = {
        clients: JSON.parse('{!! addslashes(json_encode($clients->pluck("name", "id"))) !!}'),
        warehouses: JSON.parse('{!! addslashes(json_encode($warehouses->pluck("name", "id"))) !!}'),
        payment_types: JSON.parse('{!! addslashes(json_encode($payment_types)) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        tipo_pagos: JSON.parse('{!! addslashes(json_encode($tipo_pagos->pluck("nombre", "id"))) !!}'),
        // NUEVO: Para filtrar por POS
        sessions: JSON.parse('{!! addslashes(json_encode($pos_sessions ?? [])) !!}'),
        terminals: JSON.parse('{!! addslashes(json_encode($pos_terminals ?? [])) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Gestión de Ventas">
                    <x-slot name="actions">
                        @can('create sales')
                            <x-ui.button href="{{ route('sales.create') }}" variant="primary" iconLeft="heroicon-s-plus-circle">
                                Nueva Venta
                            </x-ui.button>
                        @endcan

                        <x-data-table.export-button :route="route('sales.export')" formId="sales-filters" />
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Pipeline --}}
                @include('sales.partials.filters')

                {{-- Contenedor de Tabla AJAX --}}
                <div id="sales-table" class="w-full overflow-hidden">
                    @include('sales.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>