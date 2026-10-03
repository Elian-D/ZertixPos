<x-app-layout title="Editar: {{ $product->name }}">
    <div class="p-4 md:p-6">
        <x-ui.page-header :title="'Editar: '.$product->name"
            description="Modifica los datos del producto o servicio en el catálogo." />

        <form action="{{ route('inventory.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="mt-6">
            @csrf
            @method('PUT')

            @include('products.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.products.show', $product) }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Guardar cambios
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
