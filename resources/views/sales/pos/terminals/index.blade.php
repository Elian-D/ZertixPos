<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Configuración de Terminales POS" description="Administra las terminales de punto de venta registradas y su configuración." :count="$items->total()" countLabel="terminales">
            <x-slot name="actions">
                @can('view pos terminals')
                    <x-ui.button href="{{ route('sales.pos.terminals.eliminadas') }}"
                        appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                        Papelera
                    </x-ui.button>
                @endcan

                @can('create pos terminals')
                    <x-ui.button href="{{ route('sales.pos.terminals.create') }}"
                        variant="primary" iconLeft="heroicon-s-plus">
                        Nueva Terminal
                    </x-ui.button>
                @endcan
            </x-slot>

            <x-slot:secondary>
                @can('pos config view')
                    <x-ui.button href="{{ route('sales.pos.settings.edit') }}"
                        appearance="ghost" variant="secondary" class="w-full justify-start" iconLeft="heroicon-o-cog">
                        Configuración Global
                    </x-ui.button>
                @endcan
            </x-slot:secondary>
        </x-ui.page-header>

        {{-- FILTROS (Columnas) --}}
        @include('sales.pos.terminals.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="terminals-table" class="w-full overflow-hidden">
            @include('sales.pos.terminals.partials.table')
        </div>
    </div>
</x-app-layout>