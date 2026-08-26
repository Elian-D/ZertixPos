<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header title="Catálogo de Cuentas" description="Gestiona el catálogo de cuentas contables de la empresa." :count="$items->total()" countLabel="cuentas">
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
        </x-ui.page-header>

        {{-- FILTROS SIMPLIFICADOS (Solo Columnas y PerPage) --}}
        @include('accounting.accounts.partials.filters')

        {{-- TABLA AJAX --}}
        <div id="accounts-table" class="w-full overflow-hidden">
            @include('accounting.accounts.partials.table')
        </div>
    </div>
</x-app-layout>