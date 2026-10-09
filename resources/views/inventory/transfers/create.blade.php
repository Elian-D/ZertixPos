<x-app-layout title="Nueva transferencia">
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Nueva transferencia"
            description="Se guarda en borrador; la mercancía sale del origen cuando la envías." />

        <form method="POST" action="{{ route('inventory.transfers.store') }}" class="mt-6">
            @csrf

            @include('inventory.transfers.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.transfers.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Guardar borrador
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
