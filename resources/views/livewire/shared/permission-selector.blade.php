{{--
    Los permisos (y grupos enteros) de un módulo satélite/flexible apagado no
    aparecen acá — ni deshabilitados con motivo, directamente no están en
    `$groupedPermissions` (ver PermissionGroup::groupedForAssignment()).
    Mismo criterio que el sidebar: un link a una ruta gateada por `module:<key>`
    no aparece si el módulo está apagado, y entrar por URL directa da 404.

    Nota de implementación: las pestañas de grupo son Alpine puro (x-show),
    NO wire:click/@if — todos los grupos quedan siempre en el DOM, solo se
    ocultan con CSS. Un `@if` que quita del DOM el grupo que se deja de ver
    borraría el estado `checked` de esos checkboxes (no están wire:model,
    viven solo en el DOM) cada vez que se cambia de pestaña. El único
    re-render real por Livewire es al cambiar de rol (`wire:model.live` en el
    select) — Livewire preserva el `checked` de los inputs no controlados al
    hacer el morph del DOM, así que cambiar de rol no pierde lo ya marcado.
--}}
<div x-data="{ activeGroup: '{{ $groupedPermissions->first()?->key }}' }">
    @if ($showRoleSelect)
        <div class="mb-6">
            <x-ui.forms.select
                label="Rol en el Sistema:"
                name="role_id"
                id="role_id"
                required
                placeholder="-- Elija un Rol --"
                wire:model.live="roleId"
                :error="$errors->first('role_id')"
            >
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        <h3 class="text-base font-semibold text-gray-700 mb-4">Permisos Extra (opcional)</h3>
        <p class="text-sm text-gray-500 mb-4">
            Marcados y bloqueados: ya los otorga el rol elegido arriba. Los demás son adicionales — no hace falta
            marcarlos si el rol ya cubre lo que este usuario necesita.
        </p>
    @else
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-700">Permisos del Rol</h3>
            <div class="flex items-center space-x-3 text-sm font-medium">
                <button type="button"
                        @click="$nextTick(() => { document.querySelectorAll('input[name=\'permissions[]\']:not(:disabled)').forEach(el => el.checked = true) })"
                        class="text-zertix-primary-600 hover:text-zertix-primary-800 transition duration-150 flex items-center">
                    <x-heroicon-s-check-circle class="w-4 h-4 mr-1" />
                    Seleccionar Todos
                </button>
                <span class="text-gray-300">|</span>
                <button type="button"
                        @click="$nextTick(() => { document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => el.checked = false) })"
                        class="text-red-600 hover:text-red-800 transition duration-150 flex items-center">
                    <x-heroicon-s-x-circle class="w-4 h-4 mr-1" />
                    Quitar Todos
                </button>
            </div>
        </div>
    @endif

    <div class="border border-gray-200 rounded-lg overflow-hidden flex flex-col sm:flex-row" wire:loading.class="opacity-50" wire:target="roleId">
        {{-- Nav de grupos --}}
        <div class="sm:w-52 shrink-0 bg-gray-50 border-b sm:border-b-0 sm:border-r border-gray-200 p-2 flex flex-row sm:flex-col gap-1 overflow-x-auto sm:overflow-x-visible">
            @foreach ($groupedPermissions as $group)
                @php
                    $totalInGroup = $group->permissions->count();
                    $checkedInGroup = $group->permissions->filter(fn ($p) => in_array($p->name, $checkedNames) || in_array($p->name, $includedNames))->count();
                @endphp
                <button type="button"
                        @click="activeGroup = '{{ $group->key }}'"
                        :class="activeGroup === '{{ $group->key }}' ? 'bg-white text-zertix-primary-700 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        class="flex items-center justify-between gap-2 px-3 py-2 rounded-md text-sm text-left whitespace-nowrap transition-colors">
                    <span>{{ $group->label }}</span>
                    <span class="text-xs text-gray-400 shrink-0">{{ $checkedInGroup }}/{{ $totalInGroup }}</span>
                </button>
            @endforeach
        </div>

        {{-- Detalle — TODOS los grupos quedan renderizados, x-show solo oculta (ver nota arriba) --}}
        <div class="flex-1 p-4 min-w-0">
            @foreach ($groupedPermissions as $group)
                <div x-show="activeGroup === '{{ $group->key }}'" x-cloak wire:key="group-{{ $group->key }}">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Permisos de {{ $group->label }}</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-3">
                        @foreach ($group->permissions as $permission)
                            @php
                                $includedByRole = in_array($permission->name, $includedNames);
                                $isChecked = $includedByRole || in_array($permission->name, $checkedNames);
                            @endphp
                            <x-ui.forms.checkbox
                                label="{{ trans_permission($permission->name) }}"
                                description="{{ $includedByRole ? 'Ya incluido por el rol seleccionado.' : trans_permission($permission->name, 'description') }}"
                                name="permissions[]"
                                id="perm_{{ $permission->id }}"
                                value="{{ $permission->name }}"
                                :checked="$isChecked"
                                :disabled="$includedByRole"
                                wire:key="perm-{{ $permission->id }}"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
