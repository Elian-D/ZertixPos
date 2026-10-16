<x-app-layout title="Nueva categoría">
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Nueva categoría" description="Una categoría principal para agrupar, o una subcategoría donde van los productos." />

        <form method="POST" action="{{ route('configuration.categories.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf
            @include('products.categories.partials.form')

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('configuration.categories.index') }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Crear categoría</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
