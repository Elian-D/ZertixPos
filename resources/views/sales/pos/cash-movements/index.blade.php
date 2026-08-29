<script>
    window.filterSources = {
        users: JSON.parse('{!! addslashes(json_encode($users->pluck("name", "id"))) !!}'),
        sessions: JSON.parse('{!! addslashes(json_encode($sessions->pluck("label", "id"))) !!}'),
        types: JSON.parse('{!! addslashes(json_encode($types)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Historial de Movimientos de Caja" description="Consulta los ingresos y egresos de efectivo registrados en las cajas de las terminales POS." :count="$items->total()" countLabel="movimientos">
            <x-slot name="actions">
                @can('pos_cash_movements.create')
                    <x-ui.button @click="$dispatch('open-modal', 'register-cash-movement')"
                        variant="primary" iconLeft="heroicon-s-currency-dollar">
                        Registrar Movimiento
                    </x-ui.button>
                @endcan

                {{-- Si decides agregar exportación luego --}}
                {{-- <x-data-table.export-button :route="route('sales.pos.cash-movements.export')" formId="cash-movements-filters" /> --}}
            </x-slot>
        </x-ui.page-header>

        {{-- Filtros del Pipeline --}}
        @include('sales.pos.cash-movements.partials.filters')

        {{-- Tabla AJAX --}}
        <div id="cash-movements-table" class="w-full overflow-hidden">
            @include('sales.pos.cash-movements.partials.table')
        </div>
    </div>

    {{-- Modal Reutilizable (Pasamos null porque en el index se debe elegir la sesión o manejar lógica global) --}}
    {{-- Nota: Si el modal requiere session_id obligatorio, en el index administrativo 
         podrías necesitar un select de sesiones activas dentro del modal --}}
    @include('sales.pos.cash-movements.partials.modal-movement', ['sessionId' => null])
    @include('sales.pos.cash-movements.partials.modals-detail')
</x-app-layout>

