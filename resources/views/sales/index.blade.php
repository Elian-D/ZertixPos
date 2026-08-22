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
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Ventas" description="Consulta y administra el pipeline de ventas, desde la creación hasta el cobro." :count="$items->total()" countLabel="ventas">
            <x-slot name="actions">
                @can('create sales')
                    <x-ui.button href="{{ route('sales.create') }}" variant="primary" iconLeft="heroicon-s-plus-circle">
                        Nueva Venta
                    </x-ui.button>
                @endcan
            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                    x-on:click="const form = document.getElementById('sales-filters'); const params = form ? new URLSearchParams(new FormData(form)).toString() : ''; window.location.href = '{{ route('sales.export') }}' + (params ? '?' + params : '');"
                >
                    Exportar (Excel)
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- Filtros del Pipeline --}}
        @include('sales.partials.filters')

        {{-- Contenedor de Tabla AJAX --}}
        <div id="sales-table" class="w-full overflow-hidden">
            @include('sales.partials.table')
        </div>
    </div>
</x-app-layout>