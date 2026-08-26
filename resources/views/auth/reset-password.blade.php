<x-guest-layout>
    <div>
        <h3 class="font-bold text-2xl text-slate-800 mb-2">Restablecer contraseña</h3>
        <p class="text-sm text-slate-500">Elige una nueva contraseña para tu cuenta.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <x-ui.forms.input
            type="email"
            name="email"
            label="Correo electrónico"
            icon-left="heroicon-s-envelope"
            value="{{ old('email', $request->email) }}"
            :error="$errors->first('email')"
            required
            autofocus
            autocomplete="username"
        />

        <!-- Password -->
        <x-ui.forms.input
            type="password"
            name="password"
            label="Contraseña"
            icon-left="heroicon-s-lock-closed"
            :error="$errors->first('password')"
            required
            autocomplete="new-password"
        />

        <!-- Confirm Password -->
        <x-ui.forms.input
            type="password"
            name="password_confirmation"
            label="Confirmar contraseña"
            icon-left="heroicon-s-lock-closed"
            :error="$errors->first('password_confirmation')"
            required
            autocomplete="new-password"
        />

        <x-ui.button type="submit" variant="primary" :fullWidth="true" icon-right="heroicon-s-arrow-right" size="lg">
            Restablecer contraseña
        </x-ui.button>
    </form>
</x-guest-layout>
