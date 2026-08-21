{{-- resources/views/sales/ncf/logs/index.blade.php --}}

<script>
    window.filterSources = {
        ncf_types: JSON.parse('{!! addslashes(json_encode($ncf_types)) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Auditoría y Reportes NCF">
                    <x-slot name="actions">
                        <div class="flex flex-wrap gap-2">
                            {{-- Exportar Excel (Revisión interna) --}}
                            <x-ui.button href="{{ route('finance.ncf.logs.export.excel', request()->all()) }}"
                                appearance="ghost" variant="secondary" iconLeft="heroicon-s-document-arrow-down">
                                Excel
                            </x-ui.button>

                            {{-- Botón para abrir modal de periodo TXT --}}
                            <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'export-607-modal')"
                                appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-down-tray">
                                Generar 607 (TXT)
                            </x-ui.button>
                        </div>
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Monitor --}}
                @include('sales.ncf.logs.partials.filters')

                {{-- Contenedor de Tabla AJAX --}}
                <div id="ncf-logs-table" class="w-full overflow-hidden mt-4">
                    @include('sales.ncf.logs.partials.table')
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA PERIODO DGII --}}
    <x-modal name="export-607-modal" maxWidth="sm">
        <form action="{{ route('finance.ncf.logs.export.txt') }}" method="GET" class="p-6">
            <h3 class="text-lg font-bold text-gray-900">Exportar Reporte 607</h3>
            <p class="text-sm text-gray-600 mb-4">Seleccione el periodo fiscal para generar el archivo de texto.</p>
            
            <div>
                <x-input-label for="periodo" value="Periodo (Año/Mes)" />
                <x-text-input type="month" name="periodo" id="periodo" 
                              value="{{ now()->format('Y-m') }}" 
                              required class="w-full mt-1" />
                {{-- Convertimos YYYY-MM a YYYYMM para el controlador en el backend si es necesario --}}
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Descargar TXT</x-ui.button>
            </div>
        </form>
    </x-modal>
</x-app-layout>