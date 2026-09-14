{{-- Título/subtítulo viven en install-wizard.blade.php (fuera de la card,
     ver InstallWizard::STEP_META) — rediseño 2026-09-05. --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-7 sm:p-9">
    <form wire:submit.prevent="nextStep" class="space-y-6">
        <x-ui.forms.input
            label="Nombre Completo"
            name="adminName"
            wire:model="adminName"
            placeholder="Ej. Juan Pérez"
            icon-left="heroicon-s-user"
            :error="$errors->first('adminName')"
            required
        />

        <x-ui.forms.input
            label="Correo Electrónico"
            name="adminEmail"
            type="email"
            wire:model="adminEmail"
            placeholder="admin@empresa.com"
            icon-left="heroicon-s-envelope"
            :error="$errors->first('adminEmail')"
            required
        />

        {{-- type="password" trae el toggle mostrar/ocultar de fábrica (REQ-7.11)
             — con eso alcanza para revisar lo que se escribió, por eso el
             rediseño 2026-09-05 quita "Confirmar Contraseña" (pedido explícito
             del usuario: no aporta nada real que el toggle no cubra ya). --}}
        <x-ui.forms.input
            label="Contraseña Maestra"
            name="adminPassword"
            type="password"
            wire:model="adminPassword"
            placeholder="Mínimo 8 caracteres"
            icon-left="heroicon-s-lock-closed"
            :error="$errors->first('adminPassword')"
            required
        />

        <x-ui.button type="submit" variant="primary" :fullWidth="true" iconRight="heroicon-s-arrow-right">
            Siguiente: Datos de Empresa
        </x-ui.button>
    </form>
</div>
