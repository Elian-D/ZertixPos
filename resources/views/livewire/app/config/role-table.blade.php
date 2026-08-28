<div>

    <x-ui.page-header
        title="Gestión de Roles"
        :count="$roles->total()"
        countLabel="roles"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('config.roles.create') }}" variant="primary" iconLeft="heroicon-s-plus">
                Crear Nuevo Rol
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-data-table.base-table
        :items="$roles"
        :columns="$this->columns()"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="$this->activeFilterCount() > 0"
    >
        @forelse($roles as $role)
            <tr class="hover:bg-slate-50 transition-colors duration-150">

                <x-data-table.cell column="id" :visible="$visibleColumns">
                    <span class="font-mono text-slate-500">{{ $role->id }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <span class="font-bold text-slate-900">{{ $role->name }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $role->created_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <x-data-table.cell column="updated_at" :visible="$visibleColumns">
                    <span class="text-xs text-slate-400">{{ $role->updated_at->format('d/m/Y') }}</span>
                </x-data-table.cell>

                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.action-menu>
                            <x-ui.action-menu.item href="{{ route('config.roles.edit', $role) }}" icon="heroicon-o-pencil-square">
                                Editar
                            </x-ui.action-menu.item>
                            <x-ui.action-menu.item href="{{ route('config.roles.permissions.edit', $role) }}" icon="heroicon-o-key">
                                Asignar Permisos
                            </x-ui.action-menu.item>
                            <x-ui.action-menu.item
                                x-data @click="$dispatch('open-modal', 'confirm-deletion-{{ $role->id }}')"
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
                    <x-ui.empty-state variant="simple" title="No se encontraron roles"
                        description="Intenta ajustar los filtros de búsqueda." />
                </td>
            </tr>
        @endforelse

    </x-data-table.base-table>

    {{-- MODAL DE CONFIRMACIÓN --}}
    @foreach($roles as $role)
        <x-ui.confirm-deletion-modal
            :id="$role->id"
            :title="'¿Eliminar Rol?'"
            :itemName="$role->name"
            :type="'el rol'"
            :route="route('config.roles.destroy', $role)"
            :description="'Esta acción es irreversible. Estás a punto de eliminar el rol <strong>' . e($role->name) . '</strong>. Asegúrate de que no hay usuarios asignados a este rol antes de proceder.'"
        />
    @endforeach
</div>
