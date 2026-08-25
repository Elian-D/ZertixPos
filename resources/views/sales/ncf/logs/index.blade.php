{{-- resources/views/sales/ncf/logs/index.blade.php --}}

<script>
    window.filterSources = {
        ncf_types: JSON.parse('{!! addslashes(json_encode($ncf_types)) !!}'),
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Auditoría y Reportes NCF" description="Audita los comprobantes fiscales emitidos y genera los reportes exigidos por la DGII." :count="$items->total()" countLabel="registros">
            <x-slot:secondary>
                {{-- Exportar Excel (Revisión interna) --}}
                <x-ui.button href="{{ route('finance.ncf.logs.export.excel', request()->all()) }}"
                    appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-s-document-arrow-down">
                    Excel
                </x-ui.button>

                {{-- Botón para abrir modal de periodo TXT --}}
                <x-ui.button x-data="" x-on:click="$dispatch('open-modal', 'export-607-modal')"
                    appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-s-arrow-down-tray">
                    Generar 607 (TXT)
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- Filtros del Monitor --}}
        @include('sales.ncf.logs.partials.filters')

        {{-- Contenedor de Tabla AJAX --}}
        <div id="ncf-logs-table" class="w-full overflow-hidden">
            @include('sales.ncf.logs.partials.table')
        </div>
    </div>

    {{-- MODAL PARA PERIODO DGII --}}
    <x-modal name="export-607-modal" maxWidth="sm">
        <form action="{{ route('finance.ncf.logs.export.txt') }}" method="GET" class="p-6">
            <h3 class="text-lg font-bold text-gray-900">Exportar Reporte 607</h3>
            <p class="text-sm text-gray-600 mb-4">Seleccione el periodo fiscal para generar el archivo de texto.</p>
            
            <div>
                <x-ui.forms.input type="month" label="Periodo (Año/Mes)" name="periodo" id="periodo"
                              value="{{ now()->format('Y-m') }}"
                              required
                              hint="Genera el reporte 607 exigido por la DGII para el periodo seleccionado" />
                {{-- Convertimos YYYY-MM a YYYYMM para el controlador en el backend si es necesario --}}
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary">Descargar TXT</x-ui.button>
            </div>
        </form>
    </x-modal>
</x-app-layout>