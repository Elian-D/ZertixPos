{{--
    resources/views/profile/index.blade.php — Perfil de Usuario (REQ-7.6).
    Migrado del scaffolding de Breeze al mockup de Stitch ("Perfil de Usuario -
    ZertixPOS"). Dos tabs por ahora (Datos Personales/Seguridad) — Preferencias
    y Actividad del mockup original quedan fuera de esta pasada, sin nada real
    que mostrar todavía. Sin "Cambiar Foto": `users` no tiene columna de avatar,
    el avatar es siempre de iniciales (mismo criterio que el resto del sistema,
    ver components/sidebar/layout.blade.php).
--}}
<x-app-layout title="Mi Perfil">
    <div class="p-4 md:p-6 flex flex-col gap-6" x-data="{ tab: '{{ $errors->updatePassword->any() ? 'security' : 'personal' }}' }">

        {{-- HEADER --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-zertix-primary/10 to-transparent"></div>

            <div class="px-6 pb-6 -mt-12 flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="w-20 h-20 rounded-full bg-zertix-primary text-white flex items-center justify-center text-2xl font-bold shadow-md ring-4 ring-white flex-shrink-0">
                    {{ $user->getInitials() }}
                </div>

                <div class="flex-1 min-w-0 pb-1">
                    <h1 class="text-xl font-bold text-slate-800 truncate">{{ $user->name }}</h1>
                    <p class="text-sm text-slate-400 truncate">{{ $user->email }}</p>
                    <x-ui.badge variant="primary" size="sm" icon="heroicon-s-shield-check" class="mt-2">
                        {{ $user->getRoleNames()->first() ?? 'Usuario' }}
                    </x-ui.badge>
                </div>
            </div>

            {{-- TABS --}}
            <nav class="flex px-6 border-t border-slate-100">
                <button
                    type="button"
                    @click="tab = 'personal'"
                    :class="tab === 'personal' ? 'text-zertix-primary border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                    class="px-4 py-3.5 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2"
                >
                    <x-heroicon-s-user class="w-4 h-4" />
                    Datos Personales
                </button>

                <button
                    type="button"
                    @click="tab = 'security'"
                    :class="tab === 'security' ? 'text-zertix-primary border-zertix-primary' : 'text-slate-400 border-transparent hover:text-slate-600'"
                    class="px-4 py-3.5 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2"
                >
                    <x-heroicon-s-lock-closed class="w-4 h-4" />
                    Seguridad
                </button>
            </nav>
        </div>

        {{-- CONTENIDO --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2" x-show="tab === 'personal'" x-cloak>
                @include('profile.partials.tab-personal-data')
            </div>

            <div class="lg:col-span-2" x-show="tab === 'security'" x-cloak>
                @include('profile.partials.tab-security')
            </div>

            {{-- Sesión Actual — visible en las dos pestañas (mismo criterio
                 del mockup: es información de la sesión, no de un tab en
                 particular), dato real de la tabla `sessions`. --}}
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <h3 class="text-sm font-bold text-slate-800 mb-1">Sesión Actual</h3>
                    <p class="text-xs text-slate-400 mb-4">
                        Sesión activa desde IP {{ $currentIp }}
                    </p>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="error" appearance="outline" :fullWidth="true" iconLeft="heroicon-s-arrow-right-on-rectangle">
                            Cerrar Sesión
                        </x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
