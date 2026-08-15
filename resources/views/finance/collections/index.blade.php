<script>
    window.filterSources = {
        clients: JSON.parse('{!! addslashes(json_encode($clients->pluck("name", "id"))) !!}'),
        paymentMethods: JSON.parse('{!! addslashes(json_encode($paymentMethods->pluck("nombre", "id"))) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Recibos de Cobro">
                    <x-slot name="actions">
                        @can('create payments')
                            <a href="{{ route('finance.collections.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-xs font-semibold text-white uppercase hover:bg-indigo-700 transition">
                                <x-heroicon-s-plus class="w-4 h-4 mr-2" />
                                Nuevo Cobro
                            </a>
                        @endcan

                        <x-data-table.export-button :route="route('finance.collections.export')" formId="payments-filters" />
                    </x-slot>
                </x-page-toolbar>

                {{-- Contenedor de Filtros --}}
                @include('finance.collections.partials.filters')

                {{-- Tabla AJAX --}}
                <div id="payments-table" class="w-full overflow-hidden mt-4">
                    @include('finance.collections.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>