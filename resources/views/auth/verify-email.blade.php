<x-guest-layout>
    <div>
        <h3 class="font-bold text-2xl text-slate-800 mb-2">Verifica tu correo</h3>
        <p class="text-sm text-slate-500">
            {{ __('¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu correo electrónico haciendo clic en el enlace que acabamos de enviarte? Si no recibiste el correo, con gusto te enviaremos otro.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="text-sm font-medium text-state-success">
            {{ __('Se ha enviado un nuevo enlace de verificación al correo electrónico que proporcionaste.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit" variant="primary">
                {{ __('Reenviar correo de verificación') }}
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" appearance="ghost">
                {{ __('Cerrar sesión') }}
            </x-ui.button>
        </form>
    </div>
</x-guest-layout>
