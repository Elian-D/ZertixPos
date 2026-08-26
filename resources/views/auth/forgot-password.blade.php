<x-guest-layout>
    <div>
        <h3 class="font-bold text-2xl text-slate-800 mb-2">Recuperar contraseña</h3>
        <p class="text-sm text-slate-500">
            {{ __('¿Olvidaste tu contraseña? No hay problema. Simplemente indícanos tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña y podrás elegir una nueva.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf

        <!-- Email Address -->
        <x-ui.forms.input
            type="email"
            name="email"
            label="Correo electrónico"
            icon-left="heroicon-s-envelope"
            placeholder="tu-correo@gmail.com"
            value="{{ old('email') }}"
            :error="$errors->first('email')"
            required
            autofocus
        />

        <x-ui.button type="submit" variant="primary" :fullWidth="true" icon-right="heroicon-s-arrow-right" size="lg">
            Restablecer contraseña
        </x-ui.button>
    </form>
</x-guest-layout>
