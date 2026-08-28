<div>

    <x-ui.page-header
        title="Gestión de Usuarios"
        :count="$users->total()"
        countLabel="usuarios"
    >
        <x-slot:actions>
            {{-- Botón Crear Usuario — deshabilitado (no oculto) al llegar al límite del
                 plan (REQ-05.6): el dueño necesita saber POR QUÉ no puede, no que el
                 botón simplemente desaparezca. --}}
            <div class="flex flex-col items-end gap-1">
                @if ($canCreateMoreUsers)
                    <x-ui.button href="{{ route('config.users.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                        Crear Nuevo Usuario
                    </x-ui.button>
                    @if (! is_null($usersLimit))
                        <p class="text-xs text-slate-400">
                            {{ $totalUsersCount }}/{{ $usersLimit }} usuarios utilizados
                        </p>
                    @endif
                @else
                    <x-ui.button type="button" disabled variant="primary" iconLeft="heroicon-s-plus"
                        title="Tu plan actual permite un máximo de {{ $usersLimit }} usuario(s). Actualizá tu plan para agregar más.">
                        Crear Nuevo Usuario
                    </x-ui.button>
                    <p class="text-xs text-amber-600 font-medium">
                        Límite del plan alcanzado ({{ $totalUsersCount }}/{{ $usersLimit }} usuarios) — actualizá tu plan para agregar más.
                    </p>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$users"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        @forelse($users as $user)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="avatar" :visible="$visibleColumns">
                    <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                        @else
                            <span class="text-sm font-semibold text-slate-600">{{ $user->getInitials() }}</span>
                        @endif
                    </div>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <div class="font-bold text-slate-900">{{ $user->name }}</div>
                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                </x-data-table.cell>

                <x-data-table.cell column="roles" :visible="$visibleColumns">
                    <span class="text-slate-600">{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $user->created_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $user->updated_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.action-menu>
                            <x-ui.action-menu.item href="{{ route('config.users.edit', $user) }}" icon="heroicon-o-pencil-square">
                                Editar
                            </x-ui.action-menu.item>
                            <x-ui.action-menu.item href="{{ route('config.users.roles.edit', $user) }}" icon="heroicon-o-key">
                                Asignar Roles y Permisos
                            </x-ui.action-menu.item>
                            <x-ui.action-menu.item
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $user->id }}')"
                                icon="heroicon-o-trash" variant="danger">
                                Eliminar
                            </x-ui.action-menu.item>
                        </x-ui.action-menu>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-16">
                    <x-ui.empty-state variant="simple" title="No se encontraron usuarios"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    {{-- MODAL DE CONFIRMACIÓN --}}
    @foreach($users as $user)
        <x-ui.confirm-deletion-modal
            :id="$user->id"
            :title="'¿Eliminar Usuario?'"
            :itemName="$user->name"
            :type="'el usuario'"
            :route="route('config.users.destroy', $user)"
            :description="'Esta acción es irreversible. Estás a punto de eliminar al usuario <strong>' . e($user->name) . '</strong>.'"
        />
    @endforeach
</div>
