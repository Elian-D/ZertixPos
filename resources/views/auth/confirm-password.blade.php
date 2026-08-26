<x-guest-layout>
    <div>
        <h3 class="font-bold text-2xl text-slate-800 mb-2">Confirmar contraseña</h3>
        <p class="text-sm text-slate-500">
            {{ __('Esta es un área segura de la aplicación. Por favor confirma tu contraseña antes de continuar.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5">
        @csrf

        <!-- Password -->
        <x-ui.forms.input
            type="password"
            name="password"
            label="Contraseña"
            icon-left="heroicon-s-lock-closed"
            :error="$errors->first('password')"
            required
            autofocus
            autocomplete="current-password"
        />

        <x-ui.button type="submit" variant="primary" :fullWidth="true" icon-right="heroicon-s-arrow-right" size="lg">
            Confirmar
        </x-ui.button>
    </form>
</x-guest-layout>
