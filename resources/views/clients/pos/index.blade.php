@include('clients.pos.partials.filter-sources')

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Gestión de Puntos de Venta" description="Administra los puntos de venta y ubicaciones de entrega asociados a tus clientes." :count="$pos->total()" countLabel="puntos de venta">
            <x-slot name="actions">
                <x-ui.button href="{{ route('clients.delivery_points.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                    Papelera
                </x-ui.button>

                <x-ui.button href="{{ route('clients.delivery_points.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                    Nuevo Punto de Venta
                </x-ui.button>
            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary" appearance="ghost" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray"
                    x-on:click="const form = document.getElementById('pos-filters'); const params = form ? new URLSearchParams(new FormData(form)).toString() : ''; window.location.href = '{{ route('clients.delivery_points.export') }}' + (params ? '?' + params : '');"
                >
                    Exportar (Excel)
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- FILTROS --}}
        @include('clients.pos.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="pos-table" class="w-full overflow-hidden">
            @include('clients.pos.partials.table')
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal
        formId="pos-filters"
        route="{{ route('clients.delivery_points.bulk') }}"
    />
</x-app-layout>