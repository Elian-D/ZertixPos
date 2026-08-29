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

    {{-- Papelera como tab del mismo índice (REQ-2.7 punto 3, mismo criterio de
         docs/analisis/politica-soft-deletes.md §6) — solo Restaurar, sin
         borrado definitivo, a diferencia del resto de módulos Categoría A. --}}
    <div class="flex gap-1 mb-4">
        <x-ui.button
            size="sm"
            :variant="$filters['trashed'] === '' ? 'primary' : 'secondary'"
            :appearance="$filters['trashed'] === '' ? 'solid' : 'ghost'"
            wire:click="$set('filters.trashed', '')">
            Activos
        </x-ui.button>

        <x-ui.button
            size="sm"
            :variant="$filters['trashed'] === 'only' ? 'error' : 'secondary'"
            :appearance="$filters['trashed'] === 'only' ? 'solid' : 'ghost'"
            wire:click="$set('filters.trashed', 'only')">
            Papelera
        </x-ui.button>
    </div>

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
                        @if ($user->trashed())
                            <x-ui.button
                                appearance="ghost" variant="success" size="sm" icon="heroicon-o-arrow-path"
                                wire:click="restore({{ $user->id }})"
                                aria-label="Restaurar usuario" title="Restaurar usuario" />
                        @else
                            @php
                                // REQ-2.7 puntos 1/2: ni la propia cuenta ni el último
                                // usuario activo con el rol protegido se pueden eliminar
                                // — la acción se oculta acá, el guard real vive en
                                // UserController::destroy().
                                $canDelete = $user->id !== auth()->id() && $user->id !== $protectedUserId;
                            @endphp
                            <x-ui.action-menu>
                                <x-ui.action-menu.item href="{{ route('config.users.edit', $user) }}" icon="heroicon-o-pencil-square">
                                    Editar
                                </x-ui.action-menu.item>
                                @if ($canDelete)
                                    <x-ui.action-menu.item
                                        x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $user->id }}')"
                                        icon="heroicon-o-trash" variant="danger">
                                        Eliminar
                                    </x-ui.action-menu.item>
                                @endif
                            </x-ui.action-menu>
                        @endif
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
        @if (! $user->trashed() && $user->id !== auth()->id() && $user->id !== $protectedUserId)
            <x-ui.confirm-deletion-modal
                :id="$user->id"
                :title="'¿Eliminar Usuario?'"
                :itemName="$user->name"
                :type="'el usuario'"
                :route="route('config.users.destroy', $user)"
                :description="'Esta acción es irreversible. Estás a punto de eliminar al usuario <strong>' . e($user->name) . '</strong>.'"
            />
        @endif
    @endforeach
</div>
