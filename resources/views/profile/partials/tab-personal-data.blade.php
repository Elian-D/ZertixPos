{{--
    resources/views/profile/partials/tab-personal-data.blade.php — solo
    campos reales de `users` (name/email). Sin teléfono/foto: esas columnas
    no existen en la tabla, no se inventan solo porque el mockup las traía.
--}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
    <h2 class="text-base font-bold text-slate-800 mb-6 flex items-center gap-2">
        <x-heroicon-s-identification class="w-5 h-5 text-zertix-primary" />
        Información Personal
    </h2>

    @if(tenant()?->is_demo)
        <div class="flex items-start gap-3 bg-state-info/10 text-state-info border border-state-info/20 rounded-lg px-4 py-3 mb-6">
            <x-heroicon-s-information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
            <p class="text-sm">
                Esta es una cuenta de demostración compartida — el correo no se puede cambiar.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-ui.forms.input
                label="Nombre Completo"
                name="name"
                value="{{ old('name', $user->name) }}"
                icon-left="heroicon-o-user"
                :error="$errors->first('name')"
                required
                autofocus
            />

            <x-ui.forms.input
                label="Correo Electrónico"
                name="email"
                type="email"
                value="{{ old('email', $user->email) }}"
                icon-left="heroicon-o-envelope"
                :error="$errors->first('email')"
                :disabled="(bool) tenant()?->is_demo"
                required
            />
        </div>

        <x-ui.forms.input
            label="Rol del Sistema"
            value="{{ $user->getRoleNames()->first() ?? 'Sin rol asignado' }}"
            icon-left="heroicon-o-shield-check"
            icon-right="heroicon-o-lock-closed"
            disabled
            hint="Contacta a un administrador para cambiar tu rol."
        />

        <div class="flex justify-end pt-4 border-t border-slate-100">
            <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                Guardar Cambios
            </x-ui.button>
        </div>
    </form>
</div>
