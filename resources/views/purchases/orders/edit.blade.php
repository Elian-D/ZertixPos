<x-app-layout :title="'Editar '.$order->number">
    <div class="p-4 md:p-6">
        <x-ui.page-header :title="'Editar '.$order->number" description="Borrador: todavía se puede cambiar todo." />

        <form method="POST" action="{{ route('purchases.orders.update', $order) }}" class="mt-6">
            @csrf
            @method('PUT')

            @include('purchases.orders.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('purchases.orders.show', $order) }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Guardar cambios
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
