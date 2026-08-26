<script>
    window.filterSources = {
        customers: JSON.parse('{!! addslashes(json_encode($customers->pluck("name", "id"))) !!}'),
        users: JSON.parse('{!! addslashes(json_encode($users->pluck("name", "id"))) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        origins: JSON.parse('{!! addslashes(json_encode($origins)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Motor de Cotizaciones" description="Crea y da seguimiento a las cotizaciones enviadas a clientes antes de convertirlas en ventas." :count="$items->total()" countLabel="cotizaciones">
            <x-slot name="actions">
                @can('create quotes')
                    <x-ui.button href="{{ route('clients.quotes.create') }}" variant="primary" iconLeft="heroicon-s-plus-circle">
                        Nueva Cotización
                    </x-ui.button>
                @endcan
            </x-slot>
        </x-ui.page-header>

        {{-- Filtros del Pipeline --}}
        @include('sales.quotes.partials.filters')

        {{-- Contenedor de Tabla AJAX --}}
        <div id="quotes-table" class="w-full overflow-hidden">
            @include('sales.quotes.partials.table')
        </div>
    </div>
</x-app-layout>