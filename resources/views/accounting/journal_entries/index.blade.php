<script>
    window.filterSources = {
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Libro Diario">
                    <x-slot name="actions">
                        @can('create journal entries')
                            <x-ui.button href="{{ route('finance.journal_entries.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                                Nuevo Asiento
                            </x-ui.button>
                        @endcan

                        <x-data-table.export-button :route="route('finance.journal_entries.export')" formId="journals-filters" />
                    </x-slot>
                </x-page-toolbar>

                {{-- Filtros del Pipeline --}}
                @include('accounting.journal_entries.partials.filters')

                {{-- Tabla AJAX --}}
                <div id="journals-table" class="w-full overflow-hidden">
                    @include('accounting.journal_entries.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>