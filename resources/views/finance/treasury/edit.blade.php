<x-app-layout :title="'Editar '.$account->name">
    <div class="p-4 md:p-6">
        <x-ui.page-header :title="'Editar '.$account->name" description="El saldo no se edita: cambia con movimientos o conciliando." />

        <form method="POST" action="{{ route('finance.treasury.update', $account) }}" class="mt-6 flex flex-col gap-6">
            @csrf
            @method('PUT')
            @include('finance.treasury.partials.form')

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('finance.treasury.show', $account) }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
