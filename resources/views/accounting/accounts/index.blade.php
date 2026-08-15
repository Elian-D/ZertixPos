<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Catálogo de Cuentas">
                    <x-slot name="actions">
                        {{-- Botón para crear nueva cuenta --}}
                        <x-primary-button class="inline-flex items-center px-4 py-2 bg-blue-600 rounded-md text-xs font-semibold text-white uppercase hover:bg-blue-700" 
                            x-data x-on:click="$dispatch('open-modal', 'create-account')">
                            <x-heroicon-s-plus class="w-5 h-5 mr-2" /> Nueva Cuenta
                        </x-primary-button>
                        
                        <a href="{{ route('finance.accounts.eliminados') }}"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                            <x-heroicon-s-trash class="w-4 h-4 mr-2" />
                            Papelera
                        </a>
                    </x-slot>
                </x-page-toolbar>

                {{-- FILTROS SIMPLIFICADOS (Solo Columnas y PerPage) --}}
                @include('accounting.accounts.partials.filters')

                {{-- TABLA AJAX --}}
                <div id="accounts-table" class="w-full overflow-hidden">
                    @include('accounting.accounts.partials.table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>