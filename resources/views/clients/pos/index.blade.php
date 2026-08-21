@include('clients.pos.partials.filter-sources')

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            
            {{-- Toast Notifications --}}
            <x-ui.toasts />
            

            <div class="p-6">
                <x-page-toolbar title="Gestión de Puntos de Venta">
                    <x-slot name="actions">
                        <x-ui.button href="{{ route('clients.delivery_points.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                            Papelera
                        </x-ui.button>

                        <x-ui.button href="{{ route('clients.delivery_points.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                            Nuevo Punto de Venta
                        </x-ui.button>

                        <x-data-table.export-button :route="route('clients.delivery_points.export')" formId="pos-filters" />
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS --}}
                @include('clients.pos.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="pos-table" class="w-full overflow-hidden">
                    @include('clients.pos.partials.table')
                </div>
            </div>
        </div>
    </div>

    <x-data-table.bulk-confirmation-modal 
        formId="pos-filters" 
        route="{{ route('clients.delivery_points.bulk') }}" 
    />
</x-app-layout>