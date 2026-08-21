<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            
            {{-- Notificaciones Toast --}}
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Configuración de Terminales POS">
                    <x-slot name="actions">
                        @can('view pos terminals')
                            <x-ui.button href="{{ route('sales.pos.terminals.eliminadas') }}"
                                appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                                Papelera
                            </x-ui.button>
                        @endcan

                        @can('pos config view')
                            <x-ui.button href="{{ route('sales.pos.settings.edit') }}"
                                appearance="ghost" variant="secondary" iconLeft="heroicon-o-cog">
                                Configuración Global
                            </x-ui.button>
                        @endcan

                        @can('create pos terminals')
                            <x-ui.button href="{{ route('sales.pos.terminals.create') }}"
                                variant="primary" iconLeft="heroicon-s-plus">
                                Nueva Terminal
                            </x-ui.button>
                        @endcan
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS (Columnas) --}}
                @include('sales.pos.terminals.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="terminals-table" class="w-full overflow-hidden mt-4">
                    @include('sales.pos.terminals.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>