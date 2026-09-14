{{--
    Pantalla completa de verdad (fixed inset-0), no una tarjeta más dentro
    del layout centrado del wizard — ver install-wizard.blade.php, que la
    renderiza AFUERA del contenedor con logo/step-indicator/card cuando
    $provisioning está prendido.

    `ProvisionTenantJob` corre en la cola (Redis), no en este request — acá
    solo se sondea su estado cada 2s (`wire:poll.2s`, Livewire nativo, no
    Alpine) hasta que `checkProvisioningStatus()` detecta `done` (pantalla de
    éxito, ver abajo) o `failed` (muestra el error y deja reintentar).

    Sin spinner (decisión del usuario, 2026-09-04) — el aro+arco de
    `x-ui.loading` seguía leyéndose como una forma rota en vez de un spinner
    reconocible, sin importar el ajuste de color/opacidad. En su lugar, texto
    dinámico que rota entre las fases reales de `ProvisionTenantJob::handle()`
    en el mismo orden en que corren de verdad (seed → admin → empresa →
    plan) — no es una barra de progreso falsa (no hay forma de saber el %
    real de un seed), es simplemente decirle al usuario qué está pasando.

    `beforeunload` (rediseño 2026-09-05) — cerrar/recargar acá a mitad de
    camino no rompe nada gracias a `InstallWizard::mount()` (reengancha por
    `session('install_wizard_token')`), pero igual conviene avisar.

    **Se abandonó el auto-redirect al terminar (2026-09-05, tres intentos
    reales, ver docblock completo en `InstallWizard::checkProvisioningStatus()`):**
    ni `$this->redirect()` ni un click de `<a>` armado a mano en JS evitan el
    diálogo nativo "¿Quieres salir del sitio web?" que Chrome/Edge muestran
    en un redirect automático después de interactuar con un campo de
    contraseña — confirmado que ni el propio Odoo lo resuelve (su signup
    real cae en su propia pantalla de error al intentarlo). En vez de seguir
    peleando contra el navegador, `$provisioningDone` prende una pantalla de
    éxito con un `<a href>` real ("Entrar a mi negocio") — un click
    genuino del usuario nunca dispara ese diálogo — más un botón "Copiar"
    para pegar la URL en otra pestaña si lo prefiere.
--}}
<div
    x-data="{
        seconds: 0,
        messageIndex: 0,
        messages: [
            'Creando tu base de datos...',
            'Sembrando roles y permisos...',
            'Cargando el catálogo base...',
            'Creando tu usuario administrador...',
            'Configurando los datos de tu empresa...',
            'Activando tu plan y tu período de prueba...',
        ],
        copied: false,
        unloadGuard(e) { e.preventDefault(); e.returnValue = ''; },
        copyUrl(url) {
            navigator.clipboard.writeText(url);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
    }"
    x-init="
        setInterval(() => seconds++, 1000);
        setInterval(() => messageIndex = (messageIndex + 1) % messages.length, 2200);
        window.addEventListener('beforeunload', unloadGuard);
    "
    x-on:provisioning-done.window="window.removeEventListener('beforeunload', unloadGuard)"
    class="fixed inset-0 z-50 bg-white flex flex-col items-center justify-center px-6 text-center"
    wire:poll.2s="checkProvisioningStatus"
>
    @if ($provisioningDone)
        <div class="w-16 h-16 rounded-full bg-zertix-primary/10 flex items-center justify-center mb-2">
            <x-heroicon-s-check-circle class="w-9 h-9 text-zertix-primary" />
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">¡Tu negocio está listo!</h1>
        <p class="mt-3 text-sm text-gray-500 max-w-sm">
            Ya podés entrar a tu cuenta. Por seguridad del navegador, este último paso lo hacés vos con un click.
        </p>

        {{-- Copiar, no solo mostrar — si el click de abajo no navega por
             cualquier motivo (popup blocker, extensión, etc.), la URL real
             sigue siendo accesible pegándola en otra pestaña. --}}
        <div class="mt-5 w-full max-w-sm flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 pl-3.5 pr-2 py-2.5">
            <x-heroicon-s-link class="w-4 h-4 text-gray-400 flex-shrink-0" />
            <span class="text-sm font-medium text-gray-700 truncate flex-1 text-left">{{ $tenantLoginUrl }}</span>
            <button
                type="button"
                x-on:click="copyUrl(@js($tenantLoginUrl))"
                class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center text-gray-400 hover:text-zertix-primary hover:bg-white transition-colors"
                aria-label="Copiar URL"
            >
                <x-heroicon-s-check class="w-4 h-4 text-zertix-primary" x-cloak x-show="copied" />
                <x-heroicon-s-clipboard-document class="w-4 h-4" x-cloak x-show="!copied" />
            </button>
        </div>

        <x-ui.button href="{{ $tenantLoginUrl }}" variant="primary" iconRight="heroicon-s-arrow-right" :hoverEffect="true" class="mt-6">
            Entrar a mi negocio
        </x-ui.button>
    @elseif ($provisioningError)
        <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mb-2">
            <x-heroicon-s-exclamation-triangle class="w-8 h-8 text-red-500" />
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">No pudimos terminar</h1>
        <p class="mt-3 text-sm text-gray-500 max-w-sm">{{ $provisioningError }}</p>

        <x-ui.button type="button" variant="primary" wire:click="finish" class="mt-6" iconLeft="heroicon-s-arrow-path">
            Reintentar
        </x-ui.button>
    @else
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Creando tu negocio...</h1>

        <p class="mt-4 text-base text-zertix-primary font-semibold h-6" x-text="messages[messageIndex]"></p>

        <p class="mt-2 text-sm text-gray-400 max-w-sm">No cierres esta ventana.</p>

        <p class="mt-6 text-xs text-gray-400 font-mono" x-text="seconds + 's'"></p>
    @endif
</div>
