<x-app-layout title="Nueva cuenta">
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Nueva cuenta" description="Una caja física o una cuenta de banco, con el saldo que tiene hoy." />

        <form method="POST" action="{{ route('finance.treasury.store') }}" class="mt-6 flex flex-col gap-6">
            @csrf
            @include('finance.treasury.partials.form')

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-ui.button href="{{ route('finance.treasury.index') }}" appearance="ghost" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">Crear cuenta</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
