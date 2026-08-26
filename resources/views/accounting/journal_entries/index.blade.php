<script>
    window.filterSources = {
        statuses: JSON.parse('{!! addslashes(json_encode($statuses)) !!}'),
    };
</script>

<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Libro Diario" description="Gestiona los asientos contables del libro diario y su estado de aprobación." :count="$items->total()" countLabel="asientos">
            <x-slot name="actions">
                @can('create journal entries')
                    <x-ui.button href="{{ route('finance.journal_entries.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                        Nuevo Asiento
                    </x-ui.button>
                @endcan
            </x-slot>

            <x-slot:secondary>
                <x-ui.button
                    variant="secondary"
                    appearance="ghost"
                    size="sm"
                    class="w-full justify-start"
                    iconLeft="heroicon-s-arrow-down-tray"
                    x-data
                    x-on:click="
                        const form = document.getElementById('journals-filters');
                        const params = form ? new URLSearchParams(new FormData(form)).toString() : '';
                        window.location.href = '{{ route('finance.journal_entries.export') }}' + (params ? '?' + params : '');
                    "
                >
                    Exportar
                </x-ui.button>
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- Filtros del Pipeline --}}
        @include('accounting.journal_entries.partials.filters')

        {{-- Tabla AJAX --}}
        <div id="journals-table" class="w-full overflow-hidden">
            @include('accounting.journal_entries.partials.table')
        </div>
    </div>
</x-app-layout>