<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <div>
        <h3 class="font-bold text-2xl text-slate-800 mb-2">Iniciar sesión</h3>
        <p class="text-sm text-slate-500">Ingresa tus credenciales para continuar al panel de control.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
        @csrf

        <!-- Email Address -->
        <x-ui.forms.input
            type="email"
            name="email"
            label="Correo electrónico"
            icon-left="heroicon-s-envelope"
            placeholder="operador@tienda.com"
            value="{{ old('email') }}"
            :error="$errors->first('email')"
            required
            autofocus
            autocomplete="username"
        />

        <!-- Password (toggle mostrar/ocultar de fábrica en x-ui.forms.input
             cuando type="password", ver REQ-7.11 en docs/ui/forms.md) -->
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

        <!-- Remember Me and forgot password -->
        <div class="flex items-center justify-between -mt-1">
            <x-ui.forms.checkbox id="remember_me" name="remember" label="Recordarme" />

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-zertix-secondary hover:text-zertix-primary transition-colors" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <x-ui.button type="submit" variant="primary" :fullWidth="true" icon-right="heroicon-s-arrow-right" size="lg">
            Entrar
        </x-ui.button>
    </form>
</x-guest-layout>
