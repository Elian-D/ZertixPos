{{-- resources/views/sales/pos/sessions/index.blade.php --}}

<script>
    window.filterSources = {
        terminals: JSON.parse('{!! addslashes(json_encode($terminals->pluck('name', 'id'))) !!}'),
        users: JSON.parse('{!! addslashes(json_encode($users->pluck('name', 'id'))) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
        difference_reasons: JSON.parse('{!! addslashes(json_encode($difference_reasons)) !!}'),
    };
</script>

@if(session('autoPrintSessionUrl'))
    <script>
        // Al cerrar un turno, PosSessionController::close() flashea esta URL y redirige
        // aquí — la abrimos en pestaña nueva automáticamente (mismo patrón que el
        // auto-print de venta en PosWorkspace, Fase 7.4). Dedupe por sessionStorage por
        // si este bloque llegara a procesarse más de una vez para el mismo cierre.
        (function () {
            const url = @js(session('autoPrintSessionUrl'));
            const dedupeKey = 'pos-session-auto-printed:' + url;
            if (sessionStorage.getItem(dedupeKey)) {
                return;
            }
            sessionStorage.setItem(dedupeKey, '1');
            window.open(url, '_blank');
        })();
    </script>
@endif

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Historial de Turnos POS">
                    <x-slot name="actions">
                        <div class="flex flex-wrap gap-2">
                            {{-- Botón para abrir modal de apertura de caja --}}
                            @can('pos sessions manage')
                                <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'open-session-modal')"
                                    variant="primary" iconLeft="heroicon-s-lock-open">
                                    Nuevo Turno (Apertura)
                                </x-ui.button>
                            @endcan
                        </div>
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros (Pipeline) --}}
                @include('sales.pos.sessions.partials.filters')

                {{-- Contenedor de Tabla AJAX --}}
                <div id="pos-sessions-table" class="w-full overflow-hidden mt-4">
                    @include('sales.pos.sessions.partials.table')
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE APERTURA --}}
    @include('sales.pos.sessions.partials.modal-open')
</x-app-layout>