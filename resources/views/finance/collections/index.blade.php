<script>
    window.filterSources = {
        clients: JSON.parse('{!! addslashes(json_encode($clients->pluck("name", "id"))) !!}'),
        paymentMethods: JSON.parse('{!! addslashes(json_encode($paymentMethods->pluck("nombre", "id"))) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Recibos de Cobro" description="Registra y consulta los cobros realizados por los clientes sobre facturas y cuentas por cobrar." :count="$items->total()" countLabel="cobros">
            <x-slot name="actions">
                @can('create payments')
                    <x-ui.button href="{{ route('finance.collections.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                        Nuevo Cobro
                    </x-ui.button>
                @endcan
            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                    x-on:click="const form = document.getElementById('payments-filters'); const params = form ? new URLSearchParams(new FormData(form)).toString() : ''; window.location.href = '{{ route('finance.collections.export') }}' + (params ? '?' + params : '');"
                >
                    Exportar (Excel)
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- Contenedor de Filtros --}}
        @include('finance.collections.partials.filters')

        {{-- Tabla AJAX --}}
        <div id="payments-table" class="w-full overflow-hidden">
            @include('finance.collections.partials.table')
        </div>
    </div>
</x-app-layout>