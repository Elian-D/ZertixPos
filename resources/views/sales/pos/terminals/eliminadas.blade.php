<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">
        <x-ui.toasts />

        <x-page-toolbar title="Papelera de Terminales" subtitle="Puntos de venta desactivados que pueden ser restaurados">
            <x-slot name="actions">
                <a href="{{ route('sales.pos.terminals.index') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                    <x-heroicon-s-arrow-left class="w-4 h-4 mr-2" />
                    Volver a Terminales
                </a>
            </x-slot>
        </x-page-toolbar>

        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100 mt-6 p-6">
            <div id="terminal-trash-table">
                @include('sales.pos.terminals.partials.trashed-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>