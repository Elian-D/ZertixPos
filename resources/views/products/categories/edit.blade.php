<x-app-layout :title="'Editar '.$category->name">
    <div class="p-4 md:p-6">
        <x-ui.page-header :title="'Editar '.$category->name" description="Cambiar el nombre o la principal no toca los productos que ya tiene." />

        <form method="POST" action="{{ route('configuration.categories.update', $category) }}" class="mt-6 flex flex-col gap-6">
            @csrf
            @method('PUT')
            @include('products.categories.partials.form')

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('configuration.categories.index') }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
