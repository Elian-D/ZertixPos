{{-- Detalle del usuario — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Fila 1: perfil (2/3) | acceso (1/3). Fila 2: permisos efectivos por módulo
     (los del rol + los adicionales, estos últimos resaltados). Recibe $user, $role,
     $groups, $extraNames, $effectiveCount e $isProtectedAdmin. --}}
<x-app-layout :title="$user->name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$user->name" description="Ver usuario">
            <x-slot:actions>
                <x-ui.button href="{{ route('config.users.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @can('users.edit')
                    <x-ui.button href="{{ route('config.users.edit', $user) }}" variant="primary" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Fila 1: perfil | acceso --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <x-ui.infolist.section title="Perfil" icon="heroicon-o-user" :cols="2" class="lg:col-span-2">
                <x-ui.infolist.entry label="Nombre completo" :value="$user->name" strong />
                <x-ui.infolist.entry label="Correo electrónico" :value="$user->email" :href="'mailto:'.$user->email" />
                <x-ui.infolist.entry label="Registrado" :value="$user->created_at?->format('d/m/Y h:i A')" />
                <x-ui.infolist.entry label="Última actualización" :value="$user->updated_at?->format('d/m/Y h:i A')" />
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Acceso" icon="heroicon-o-shield-check" :cols="2">
                <x-ui.infolist.entry label="Rol" class="col-span-2">
                    @if($role)
                        @can('roles.view')
                            <a href="{{ route('config.roles.show', $role) }}">
                                <x-ui.badge variant="primary" size="sm" :dot="false" icon="heroicon-s-shield-check">{{ $role->name }}</x-ui.badge>
                            </a>
                        @else
                            <x-ui.badge variant="primary" size="sm" :dot="false" icon="heroicon-s-shield-check">{{ $role->name }}</x-ui.badge>
                        @endcan
                    @endif
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Permisos totales">
                    <span class="text-xl font-bold text-gray-900">{{ $effectiveCount }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Adicionales">
                    <span class="text-xl font-bold {{ count($extraNames) ? 'text-zertix-primary-700' : 'text-gray-900' }}">{{ count($extraNames) }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Email verificado" class="col-span-2">
                    @if($user->email_verified_at)
                        <x-ui.badge variant="success" size="sm" :dot="false">Verificado el {{ $user->email_verified_at->format('d/m/Y') }}</x-ui.badge>
                    @else
                        <x-ui.badge variant="slate" size="sm" :dot="false">No verificado</x-ui.badge>
                    @endif
                </x-ui.infolist.entry>
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: permisos efectivos --}}
        <x-ui.infolist.section title="Permisos por módulo" icon="heroicon-o-key" :cols="0"
            :description="$isProtectedAdmin
                ? 'Administrador: tiene todos los permisos del sistema.'
                : 'Lo que esta cuenta puede hacer: los permisos de su rol y, resaltados, los adicionales asignados solo a ella.'">
            @if(count($extraNames))
                <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><x-ui.badge variant="slate" size="sm" :dot="false">Permiso</x-ui.badge> del rol</span>
                    <span class="inline-flex items-center gap-1.5"><x-ui.badge variant="primary" size="sm" :dot="false" icon="heroicon-s-plus-circle">Permiso</x-ui.badge> adicional</span>
                </div>
            @endif
            @include('partials.permission-summary', ['groups' => $groups, 'extra' => $extraNames])
        </x-ui.infolist.section>
    </div>
</x-app-layout>
