<script>
    window.filterSources = {
        clients: JSON.parse('{!! addslashes(json_encode($clients->pluck("name", "id"))) !!}'),
        payment_types: JSON.parse('{!! addslashes(json_encode($payment_types)) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        formats: JSON.parse('{!! addslashes(json_encode($formats)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Historial de Facturación" description="Consulta el historial completo de facturas emitidas y su estado de pago." :count="$items->total()" countLabel="facturas">
            <x-slot:secondary>
                <x-ui.button
                    variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                    x-on:click="const form = document.getElementById('invoices-filters'); const params = form ? new URLSearchParams(new FormData(form)).toString() : ''; window.location.href = '{{ route('finance.invoices.export') }}' + (params ? '?' + params : '');"
                >
                    Exportar (Excel)
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- Filtros del Pipeline específicos para Facturas --}}
        @include('sales.invoices.partials.filters')

        {{-- Contenedor de Tabla AJAX --}}
        <div id="invoices-table" class="w-full overflow-hidden">
            @include('sales.invoices.partials.table')
        </div>
    </div>
</x-app-layout>