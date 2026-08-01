<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            
            {{-- Notificaciones Toast --}}
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Configuración de Terminales POS">
                    <x-slot name="actions">
                        @can('view pos terminals')
                            <a href="{{ route('sales.pos.terminals.eliminadas') }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                                <x-heroicon-s-trash class="w-4 h-4 mr-2" />
                                Papelera
                            </a>
                        @endcan

                        @can('pos config view')  
                            <a href="{{ route('sales.pos.settings.edit') }}" 
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ml-3">
                                <x-heroicon-o-cog class="w-4 h-4 mr-2" />  <!-- o s-cog / o-cog-6-tooth -->
                                Configuración Global
                            </a>
                        @endcan

                        @can('create pos terminals')
                            <a href="{{ route('sales.pos.terminals.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 rounded-md text-xs font-semibold text-white uppercase hover:bg-green-700 transition">
                                <x-heroicon-s-plus class="w-4 h-4 mr-2" />
                                Nueva Terminal
                            </a>
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