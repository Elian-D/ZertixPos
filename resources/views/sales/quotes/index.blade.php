<script>
    window.filterSources = {
        customers: JSON.parse('{!! addslashes(json_encode($customers->pluck("name", "id"))) !!}'),
        users: JSON.parse('{!! addslashes(json_encode($users->pluck("name", "id"))) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        origins: JSON.parse('{!! addslashes(json_encode($origins)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Motor de Cotizaciones">
                    <x-slot name="actions">
                        @can('create quotes')
                            <x-ui.button href="{{ route('sales.quotes.create') }}" variant="primary" iconLeft="heroicon-s-plus-circle">
                                Nueva Cotización
                            </x-ui.button>
                        @endcan
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Pipeline --}}
                @include('sales.quotes.partials.filters')

                {{-- Contenedor de Tabla AJAX --}}
                <div id="quotes-table" class="w-full overflow-hidden">
                    @include('sales.quotes.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>