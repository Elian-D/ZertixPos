@push('scripts')
    <script>
        window.filterSources = {
            clients: JSON.parse('{!! addslashes(json_encode($clients->pluck("name", "id"))) !!}'),
            statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        };
    </script>
@endpush

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Cuentas por Cobrar (Facturación)" description="Gestiona las facturas pendientes de cobro y su estado de pago." :count="$items->total()" countLabel="cuentas" />

        {{-- Filtros del Pipeline --}}
        @include('accounting.receivables.partials.filters')

        {{-- Tabla AJAX --}}
        <div id="receivables-table" class="w-full overflow-hidden">
            @include('accounting.receivables.partials.table')
        </div>
    </div>
</x-app-layout>