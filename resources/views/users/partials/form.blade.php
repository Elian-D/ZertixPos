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

            {{-- Password y Password Confirmation se dejan con <input> nativo: el botón
                 de mostrar/ocultar contraseña (ícono clickeable superpuesto que cambia
                 `:type`) no encaja en x-ui.forms.input — iconRight solo acepta un
                 heroicon estático sin @click, no un botón interactivo. --}}
            <div x-data="{ show: false }" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña @if(isset($user)) (dejar en blanco para no cambiarla) @else (Mín. 8 caracteres) @endif:
                    </label>

                    <div class="relative">
                        <input :type="show ? 'text' : 'password'"
                                name="password"
                                id="password"
                                placeholder="Escribe una contraseña segura"
                                @unless(isset($user)) required @endunless
                                minlength="8"
                                class="w-full border-gray-300 rounded-md shadow-sm text-base py-2.5 pl-4 pr-12
                                        focus:border-zertix-primary-500 focus:ring focus:ring-zertix-primary-500 focus:ring-opacity-50
                                        @error('password') border-red-500 focus:border-red-500 focus:ring-red-200 @enderror">

                        <button type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                title="Mostrar/Ocultar Contraseña">
                            <template x-if="!show">
                                <x-heroicon-s-eye-slash class="w-5 h-5" />
                            </template>
                            <template x-if="show">
                                <x-heroicon-s-eye class="w-5 h-5" />
                            </template>
                        </button>
                    </div>

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña:</label>

                    <div class="relative">
                        <input :type="show ? 'text' : 'password'"
                                name="password_confirmation"
                                id="password_confirmation"
                                placeholder="Repite la contraseña"
                                @unless(isset($user)) required @endunless
                                minlength="8"
                                class="w-full border-gray-300 rounded-md shadow-sm text-base py-2.5 pl-4 pr-12
                                        focus:border-zertix-primary-500 focus:ring focus:ring-zertix-primary-500 focus:ring-opacity-50
                                        @error('password') border-red-500 focus:border-red-500 focus:ring-red-200 @enderror">
                    </div>
                </div>
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
