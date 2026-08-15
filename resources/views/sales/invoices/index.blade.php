<script>
    window.filterSources = {
        clients: JSON.parse('{!! addslashes(json_encode($clients->pluck("name", "id"))) !!}'),
        payment_types: JSON.parse('{!! addslashes(json_encode($payment_types)) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        formats: JSON.parse('{!! addslashes(json_encode($formats)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Historial de Facturación">
                    <x-slot name="actions">
                        {{-- Botón de Exportación (Excel) --}}
                        <x-data-table.export-button :route="route('finance.invoices.export')" formId="invoices-filters" />
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Pipeline específicos para Facturas --}}
                @include('sales.invoices.partials.filters')

                {{-- Contenedor de Tabla AJAX --}}
                <div id="invoices-table" class="w-full overflow-hidden">
                    @include('sales.invoices.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>