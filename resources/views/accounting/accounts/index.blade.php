<x-app-layout>
    <div class="w-full max-w-7xl mx-auto py-4 px-2 sm:px-3 lg:px-4">
        <div class="bg-white shadow-xl rounded-xl">
            <x-ui.toasts />

            <div class="p-6">
                <x-page-toolbar title="Catálogo de Cuentas">
                    <x-slot name="actions">
                        {{-- Botón para crear nueva cuenta --}}
                        <x-ui.button variant="primary"
                            iconLeft="heroicon-s-plus"
                            x-data x-on:click="$dispatch('open-modal', 'create-account')">
                            Nueva Cuenta
                        </x-ui.button>

                        <x-ui.button href="{{ route('finance.accounts.eliminados') }}" appearance="ghost" variant="secondary" iconLeft="heroicon-s-trash">
                            Papelera
                        </x-ui.button>
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