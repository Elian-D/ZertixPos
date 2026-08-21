<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">
        <x-ui.toasts />

        <x-page-toolbar title="Papelera de Terminales" subtitle="Puntos de venta desactivados que pueden ser restaurados">
            <x-slot name="actions">
                <x-ui.button href="{{ route('sales.pos.terminals.index') }}"
                    appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver a Terminales
                </x-ui.button>
            </x-slot>
        </x-page-toolbar>

        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100 mt-6 p-6">
            <div id="terminal-trash-table">
                @include('sales.pos.terminals.partials.trashed-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>