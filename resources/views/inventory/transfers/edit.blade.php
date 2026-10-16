<x-app-layout :title="'Editar '.$transfer->number">
    <div class="p-4 md:p-6">
        <x-ui.page-header :title="'Editar '.$transfer->number" description="Borrador: todavía no movió existencias." />

        <form method="POST" action="{{ route('inventory.transfers.update', $transfer) }}" class="mt-6">
            @csrf
            @method('PUT')

            @include('inventory.transfers.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.transfers.show', $transfer) }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Guardar cambios
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
