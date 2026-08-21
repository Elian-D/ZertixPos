<script>
    window.filterSources = {
        users: JSON.parse('{!! addslashes(json_encode($users->pluck("name", "id"))) !!}'),
        sessions: JSON.parse('{!! addslashes(json_encode($sessions->pluck("label", "id"))) !!}'),
        types: JSON.parse('{!! addslashes(json_encode($types)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Historial de Movimientos de Caja">
                    <x-slot name="actions">
                        @can('pos cash movements create')
                            <x-ui.button @click="$dispatch('open-modal', 'register-cash-movement')"
                                variant="primary" iconLeft="heroicon-s-currency-dollar">
                                Registrar Movimiento
                            </x-ui.button>
                        @endcan

                        {{-- Si decides agregar exportación luego --}}
                        {{-- <x-data-table.export-button :route="route('sales.pos.cash-movements.export')" formId="cash-movements-filters" /> --}}
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Pipeline --}}
                @include('sales.pos.cash-movements.partials.filters')

                {{-- Tabla AJAX --}}
                <div id="cash-movements-table" class="w-full overflow-hidden mt-4">
                    @include('sales.pos.cash-movements.partials.table')
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Reutilizable (Pasamos null porque en el index se debe elegir la sesión o manejar lógica global) --}}
    {{-- Nota: Si el modal requiere session_id obligatorio, en el index administrativo 
         podrías necesitar un select de sesiones activas dentro del modal --}}
    @include('sales.pos.cash-movements.partials.modal-movement', ['sessionId' => null])
    @include('sales.pos.cash-movements.partials.modals-detail')
</x-app-layout>

