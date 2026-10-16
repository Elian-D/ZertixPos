<x-app-layout title="Nuevo producto">
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Nuevo producto o servicio"
            description="Registra un artículo en el catálogo y, si es un producto, cuántas unidades tienes hoy." />

        <form action="{{ route('inventory.products.store') }}" method="POST" enctype="multipart/form-data" class="mt-6">
            @csrf

            @include('products.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('inventory.products.index') }}" appearance="ghost" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Crear
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
