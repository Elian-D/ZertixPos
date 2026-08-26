<x-app-layout>
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <x-ui.page-header title="Papelera de Terminales" description="Puntos de venta desactivados que pueden ser restaurados." :count="$items->total()" countLabel="terminales">
            <x-slot name="actions">
                <x-ui.button href="{{ route('sales.pos.terminals.index') }}"
                    appearance="ghost" variant="secondary" iconLeft="heroicon-s-arrow-left">
                    Volver a Terminales
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div id="terminal-trash-table">
            @include('sales.pos.terminals.partials.trashed-table', ['items' => $items])
        </div>
    </div>
</x-app-layout>