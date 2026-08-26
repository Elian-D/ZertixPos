{{--
    Estructura de Orvian (ComponentsTEMP/sidebar/layout.blade.php), adaptada a
    ZertixPOS — no solo recoloreada. Diferencias reales:
    - `$sidebarOpen` vive en el <html> de app-layout.blade.php (no hay
      dualidad Hub/SuperAdmin ni rutas admin.* en ZertixPOS).
    - Sin `hasHover` (REQ-7.9, segunda pasada): Orvian expande todo el ancho
      del panel al pasar el mouse sobre el sidebar colapsado — el usuario lo
      encontró incómodo/brusco incluso en el propio Orvian. Colapsado se
      queda colapsado; ver dropdown.blade.php/item.blade.php para el
      reemplazo (tooltip + flyout por clic).
    - Sin `dark:*` — no hay modo oscuro real implementado (mismo criterio que
      el resto de la Fase 7).
    - Sin `x-ui.avatar` (no existe en ZertixPOS): el avatar de iniciales se
      resuelve inline con `Auth::user()->getInitials()`, mismo patrón que ya
      usaba `components/header.blade.php`.
    - orvian-orange/orvian-blue → zertix-primary/zertix-secondary.
--}}
<aside
    class="fixed inset-y-0 left-0 sm:relative z-50 flex-shrink-0 transition-[width,transform] duration-300 ease-in-out"
    :class="{
        'w-80': sidebarOpen,
        'w-20': !sidebarOpen,
        'translate-x-0': sidebarOpen,
        '-translate-x-full sm:translate-x-0': !sidebarOpen,
    }"
>
    <div class="absolute inset-y-0 left-0 w-full bg-white border-r border-slate-100 flex flex-col shadow-xl">
        <div class="h-20 flex items-center px-4 border-b border-slate-100 overflow-hidden flex-shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center w-full justify-center transition-all duration-300">
                <div x-show="sidebarOpen" x-cloak class="flex items-center justify-center">
                    <x-ui.application-logo type="full" />
                </div>

                <div x-show="!sidebarOpen" x-cloak class="flex items-center justify-center">
                    <x-ui.application-logo type="icon" />
                </div>
            </a>
        </div>

        {{-- openDropdown: id del único dropdown de módulo abierto a la vez (acordeón,
             expandido) o del único flyout abierto (colapsado) — ver dropdown.blade.php.
             $watch resetea a null al colapsar: sin esto, un módulo activo dejaba
             openDropdown ya seteado desde el acordeón expandido, y al colapsar el
             sidebar el flyout de ese mismo id aparecía abierto sin que el usuario
             hiciera clic (bug real reportado, "queda así abierto siempre"). --}}
        <nav x-data="{ openDropdown: null }"
             x-init="$watch('sidebarOpen', open => { if (!open) openDropdown = null })"
             class="flex-1 px-4 py-6 space-y-2 overflow-y-auto overflow-x-hidden custom-scroll">
            {{ $slot }}
        </nav>

        <div class="m-3 border border-slate-100 rounded-2xl bg-slate-50 relative transition-all duration-300 ease-in-out flex-shrink-0"
            :class="sidebarOpen ? 'p-3' : 'p-1.5'"
            x-data="{ userMenuOpen: false }">

            <button @click="userMenuOpen = !userMenuOpen"
                class="flex items-center w-full relative transition-all duration-300"
                :class="sidebarOpen ? 'gap-3' : 'justify-center'">

                <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0 border-2 border-transparent">
                    @if (Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                    @else
                        <span class="text-xs font-semibold text-slate-600">{{ Auth::user()->getInitials() }}</span>
                    @endif
                </div>

                <div x-show="sidebarOpen" x-cloak class="flex-1 min-w-0 text-left">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500 truncate">
                        {{ Auth::user()->getRoleNames()->first() ?? 'Usuario' }}
                    </p>
                </div>

                <x-heroicon-s-chevron-up-down x-show="sidebarOpen" x-cloak class="w-5 h-5 text-slate-500 flex-shrink-0" />
            </button>

            {{-- Colapsado: flyout a la derecha del avatar (mismo patrón que el flyout
                 de dropdown.blade.php). Expandido: panel arriba, ancho completo. --}}
            <div x-show="userMenuOpen" x-cloak
                @click.away="userMenuOpen = false"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                :class="sidebarOpen
                    ? 'absolute bottom-full left-0 mb-3 w-full'
                    : 'absolute left-full bottom-0 ml-2 w-64'"
                class="bg-white border border-slate-100 rounded-2xl shadow-2xl p-2 z-50 origin-bottom-left">

                <div class="px-3 py-2 border-b border-slate-100 mb-2 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                        @if (Auth::user()->avatar_url)
                            <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                        @else
                            <span class="text-xs font-semibold text-slate-600">{{ Auth::user()->getInitials() }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-xs text-slate-500 truncate">
                            {{ Auth::user()->email }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('profile.edit') }}"
                class="flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm
                        text-slate-600
                        hover:bg-zertix-secondary/5
                        hover:text-zertix-secondary
                        transition duration-200 group">
                    <x-heroicon-s-user class="w-4 h-4 text-slate-400 group-hover:text-zertix-secondary" />
                    <span>Mi Perfil</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm
                                text-slate-600
                                hover:bg-red-50
                                hover:text-red-600 transition duration-200 group">
                        <x-heroicon-s-arrow-left-on-rectangle class="w-4 h-4 text-slate-400 group-hover:text-red-600" />
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
