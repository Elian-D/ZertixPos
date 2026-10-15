<x-app-layout :title="'Editar '.$vendor->display_name">
    <div class="p-4 md:p-6">
        <x-ui.page-header :title="'Editar '.$vendor->display_name" description="Sus datos, contacto y términos de pago." />

        <form method="POST" action="{{ route('purchases.vendors.update', $vendor) }}" class="mt-6">
            @csrf
            @method('PUT')
            @include('purchases.vendors.partials.form')

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('purchases.vendors.show', $vendor) }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
