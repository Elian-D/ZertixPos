<x-app-layout title="Nueva orden de compra">
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Nueva orden de compra"
            description="Se guarda en borrador. Al aprobarla ya no se edita y se puede recibir." />

        <form method="POST" action="{{ route('purchases.orders.store') }}" class="mt-6">
            @csrf

            @include('purchases.orders.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('purchases.orders.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Guardar borrador
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
