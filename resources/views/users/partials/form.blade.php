{{--
    Campos compartidos entre users/create.blade.php y users/edit.blade.php —
    mismo criterio que resources/views/sales/pos/terminals/partials/form-fields.blade.php:
    un solo partial editado una vez, en vez de mantener dos formularios casi
    idénticos en paralelo. Estilo "secciones tipo Filament" (tarjeta blanca +
    ícono + título en versalitas por sección) en una sola columna.

    Variables esperadas (todas opcionales — create.blade.php no define $user):
    - $user (User|null): presente solo en edit.
    - $userRoleId (int|null): rol actual, solo en edit.
    - $canAssign (bool|null): gate de 'users.assign' (ver UserController::edit()) —
      ausente/true en create (el rol es obligatorio para cualquiera con 'users.create').
    - $userExtraPermissions (array|null): permisos directos ya asignados, solo en edit.
--}}
<div class="space-y-6">

    {{-- Datos del Usuario --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
            <x-heroicon-s-user class="w-5 h-5 text-zertix-secondary" />
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Datos del Usuario</h3>
        </div>

        <div class="space-y-6">
            <x-ui.forms.input
                label="Nombre del Usuario:"
                type="text"
                name="name"
                id="name"
                value="{{ old('name', $user->name ?? '') }}"
                placeholder="Ej: Juan Pérez"
                required
                :error="$errors->first('name')"
            />

            <x-ui.forms.input
                label="Correo Electrónico:"
                type="email"
                name="email"
                id="email"
                value="{{ old('email', $user->email ?? '') }}"
                placeholder="ejemplo@dominio.com"
                required
                :error="$errors->first('email')"
            />

            {{-- x-ui.forms.input trae el toggle mostrar/ocultar de fábrica para
                 type="password" (docs/ui/forms.md, REQ-7.11) — no hay que
                 reimplementarlo a mano con Alpine. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.forms.input
                    type="password"
                    label="Contraseña"
                    name="password"
                    id="password"
                    icon-left="heroicon-s-lock-closed"
                    placeholder="{{ isset($user) ? 'Dejar en blanco para no cambiarla' : 'Escribe una contraseña segura' }}"
                    :hint="isset($user) ? null : 'Mín. 8 caracteres'"
                    :required="! isset($user)"
                    minlength="8"
                    :error="$errors->first('password')"
                />

                <x-ui.forms.input
                    type="password"
                    label="Confirmar Contraseña"
                    name="password_confirmation"
                    id="password_confirmation"
                    icon-left="heroicon-s-lock-closed"
                    placeholder="Repite la contraseña"
                    :required="! isset($user)"
                    minlength="8"
                />
            </div>
        </div>
    </section>

    {{-- Rol y Permisos — gateado por 'users.assign' en edit (cambiar el rol de
         un usuario existente es más sensible que editar su nombre/email; ver
         UserController::edit()). En create siempre se muestra: el rol es
         obligatorio para cualquiera con 'users.create'. --}}
    @if ($canAssign ?? true)
        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
                <x-heroicon-s-key class="w-5 h-5 text-zertix-secondary" />
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Rol y Permisos</h3>
            </div>

            <livewire:shared.permission-selector
                :show-role-select="true"
                :role-id="old('role_id', $userRoleId ?? null)"
                :checked-names="old('permissions', $userExtraPermissions ?? [])"
            />
        </section>
    @endif
</div>
