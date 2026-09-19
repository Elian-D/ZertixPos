{{--
    resources/views/profile/partials/tab-security.blade.php — cambio de
    contraseña. Reglas/guard de cuenta demo ya existían server-side
    (App\Http\Requests\Auth\UpdatePasswordRequest, REQ-3.9) — acá solo se
    refleja en la UI para no dejar que el usuario llene el form y se
    encuentre con el error recién al enviar.
--}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
    <h2 class="text-base font-bold text-slate-800 mb-6 flex items-center gap-2">
        <x-heroicon-s-lock-closed class="w-5 h-5 text-zertix-primary" />
        Cambiar Contraseña
    </h2>

    @if(tenant()?->is_demo)
        <div class="flex items-start gap-3 bg-state-info/10 text-state-info border border-state-info/20 rounded-lg px-4 py-3">
            <x-heroicon-s-information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
            <p class="text-sm">
                Esta es una cuenta de demostración compartida — la contraseña no se puede cambiar.
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            @method('put')

            <x-ui.forms.input
                type="password"
                label="Contraseña Actual"
                name="current_password"
                icon-left="heroicon-s-lock-closed"
                :error="$errors->updatePassword->first('current_password')"
                autocomplete="current-password"
                required
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.forms.input
                    type="password"
                    label="Nueva Contraseña"
                    name="password"
                    icon-left="heroicon-s-key"
                    :error="$errors->updatePassword->first('password')"
                    autocomplete="new-password"
                    required
                />

                <x-ui.forms.input
                    type="password"
                    label="Confirmar Nueva Contraseña"
                    name="password_confirmation"
                    icon-left="heroicon-s-key"
                    :error="$errors->updatePassword->first('password_confirmation')"
                    autocomplete="new-password"
                    required
                />
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check">
                    Actualizar Contraseña
                </x-ui.button>
            </div>
        </form>
    @endif
</div>
