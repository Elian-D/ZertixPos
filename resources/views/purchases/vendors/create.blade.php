<x-app-layout title="Nuevo proveedor">
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Nuevo proveedor" description="A quién le compras: sus datos, cómo contactarlo y cómo le pagas." />

        <form method="POST" action="{{ route('purchases.vendors.store') }}" class="mt-6">
            @csrf
            @include('purchases.vendors.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('purchases.vendors.index') }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Crear proveedor</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
