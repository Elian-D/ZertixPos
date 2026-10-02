{{-- Detalle del rol — patrón Infolist (/filament-show, docs/ui/infolist.md).
     Fila 1: usuarios con el rol (2/3) | datos del rol (1/3). Fila 2: permisos por
     módulo en tarjetas internas (partials/permission-summary). Recibe $role
     (permissions cargados), $groups, $users, $usersCount y $usersLimit. --}}
<x-app-layout :title="'Rol '.$role->name">
    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header :title="$role->name" description="Ver rol">
            <x-slot:actions>
                <x-ui.button href="{{ route('config.roles.index') }}" variant="secondary" appearance="outline" iconLeft="heroicon-s-arrow-left">
                    Volver
                </x-ui.button>
                @can('roles.edit')
                    <x-ui.button href="{{ route('config.roles.edit', $role) }}" variant="primary" iconLeft="heroicon-s-pencil-square">
                        Editar
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Fila 1: usuarios | datos --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <x-ui.infolist.section title="Usuarios con este rol" icon="heroicon-o-users" :cols="0" class="lg:col-span-2"
                :description="$usersCount.' '.($usersCount === 1 ? 'usuario' : 'usuarios')">
                <x-ui.infolist.repeatable :empty="$users->isEmpty()" emptyIcon="heroicon-o-users"
                    emptyTitle="Sin usuarios" emptyDescription="Ningún usuario tiene este rol todavía.">
                    @foreach($users as $user)
                        <x-ui.infolist.repeatable-item :cols="3">
                            <x-ui.infolist.entry label="Nombre" :value="$user->name" strong
                                :href="auth()->user()->can('users.view') ? route('config.users.show', $user) : null" />
                            <x-ui.infolist.entry label="Correo" :value="$user->email" class="sm:col-span-2" />
                        </x-ui.infolist.repeatable-item>
                    @endforeach
                </x-ui.infolist.repeatable>

                @if($usersCount > $usersLimit)
                    <p class="mt-4 text-xs text-slate-500">
                        Mostrando {{ $usersLimit }} de {{ $usersCount }}.
                        <a href="{{ route('config.users.index') }}" class="font-semibold text-zertix-primary-700 hover:underline">Ver todos los usuarios</a>
                    </p>
                @endif
            </x-ui.infolist.section>

            <x-ui.infolist.section title="Datos del rol" icon="heroicon-o-shield-check" :cols="2">
                <x-ui.infolist.entry label="Nombre" :value="$role->name" strong class="col-span-2" />
                <x-ui.infolist.entry label="Permisos">
                    <span class="text-xl font-bold text-gray-900">{{ $role->permissions->count() }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Usuarios">
                    <span class="text-xl font-bold text-gray-900">{{ $usersCount }}</span>
                </x-ui.infolist.entry>
                <x-ui.infolist.entry label="Creado" :value="$role->created_at?->format('d/m/Y h:i A')" class="col-span-2" />
                <x-ui.infolist.entry label="Actualizado" :value="$role->updated_at?->format('d/m/Y h:i A')" class="col-span-2" />
            </x-ui.infolist.section>
        </div>

        {{-- Fila 2: permisos por módulo --}}
        <x-ui.infolist.section title="Permisos por módulo" icon="heroicon-o-key" :cols="0"
            description="Lo que este rol puede hacer, agrupado por módulo.">
            @include('partials.permission-summary', ['groups' => $groups])
        </x-ui.infolist.section>
    </div>
</x-app-layout>
