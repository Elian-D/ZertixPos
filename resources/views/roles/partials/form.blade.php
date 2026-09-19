{{--
    Campos compartidos entre roles/create.blade.php y roles/edit.blade.php —
    mismo criterio que resources/views/sales/pos/terminals/partials/form-fields.blade.php:
    un solo partial editado una vez, en vez de mantener dos formularios casi
    idénticos en paralelo. Estilo "secciones tipo Filament" (tarjeta blanca +
    ícono + título en versalitas por sección) pero en una sola columna — a
    diferencia del layout de 3 columnas de terminals/form-fields.blade.php, acá
    solo hay dos secciones y ninguna es lo bastante angosta como para justificar
    partir la página en columnas.

    Variables esperadas (todas opcionales — create.blade.php no define $role):
    - $role (Role|null): presente solo en edit.
    - $rolePermissions (array|null): nombres de permiso ya asignados, solo en edit.
--}}
<div class="space-y-6">

    {{-- Datos del Rol --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
            <x-heroicon-s-identification class="w-5 h-5 text-zertix-secondary" />
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Datos del Rol</h3>
        </div>

        <x-ui.forms.input
            label="Nombre del Rol:"
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $role->name ?? '') }}"
            placeholder="Ej: Administrador, Vendedor, Logística"
            required
            :error="$errors->first('name')"
        />
    </section>

    {{-- Permisos del Rol — Livewire por la interactividad real que necesita
         Usuarios (ver users/partials/form.blade.php); acá solo reusa el mismo
         componente sin selector de rol. --}}
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-3">
            <x-heroicon-s-key class="w-5 h-5 text-zertix-secondary" />
            <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider">Permisos del Rol</h3>
        </div>

        <livewire:shared.permission-selector :checked-names="old('permissions', $rolePermissions ?? [])" />
    </section>
</div>
