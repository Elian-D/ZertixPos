<x-guest-layout>
    <div>
        <h3 class="font-bold text-2xl text-slate-800 mb-2">Panel de Súper Admin</h3>
        <p class="text-sm text-slate-500">Acceso exclusivo para staff de ZertixPOS.</p>
    </div>

    <form method="POST" action="{{ route('admin.login.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.forms.input
            type="email"
            name="email"
            label="Correo electrónico"
            icon-left="heroicon-s-envelope"
            placeholder="staff@zertixpos.com"
            value="{{ old('email') }}"
            :error="$errors->first('email')"
            required
            autofocus
            autocomplete="username"
        />

        <x-ui.forms.input
            type="password"
            name="password"
            label="Contraseña"
            icon-left="heroicon-s-lock-closed"
            placeholder="••••••••"
            :error="$errors->first('password')"
            required
            autocomplete="current-password"
        />

        <div class="flex items-center -mt-1">
            <x-ui.forms.checkbox id="remember_me" name="remember" label="Recordarme" />
        </div>

        <x-ui.button type="submit" variant="primary" :fullWidth="true" icon-right="heroicon-s-arrow-right" size="lg">
            Entrar
        </x-ui.button>
    </form>
</x-guest-layout>
