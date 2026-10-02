{{-- Permisos agrupados por módulo, en tarjetas internas — show de Rol y de Usuario.
     Recibe:
       $groups — PermissionGroup::groupPermissions(...): [['label' => ..., 'permissions' => Collection<Permission>]]
       $extra  — (opcional) nombres de permisos directos del usuario: se pintan distinto
                 a los heredados del rol. Sin $extra, todos los badges son iguales. --}}
@php $extra = $extra ?? null; @endphp

@if($groups->isEmpty())
    <x-ui.empty-state variant="simple" icon="heroicon-o-key" title="Sin permisos"
        description="Todavía no tiene ningún permiso asignado." />
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
        @foreach($groups as $group)
            <div class="rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
                    <h4 class="text-sm font-semibold text-gray-900">{{ $group['label'] }}</h4>
                    <span class="text-xs text-gray-400">{{ $group['permissions']->count() }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5 p-4">
                    @foreach($group['permissions'] as $permission)
                        @php $isExtra = $extra !== null && in_array($permission->name, $extra, true); @endphp
                        <x-ui.badge :variant="$isExtra ? 'primary' : 'slate'" size="sm" :dot="false"
                            :icon="$isExtra ? 'heroicon-s-plus-circle' : null"
                            title="{{ trans_permission($permission->name, 'description') }}">
                            {{ trans_permission($permission->name) }}
                        </x-ui.badge>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
