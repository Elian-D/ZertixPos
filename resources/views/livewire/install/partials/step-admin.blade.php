<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-10">
    <h1 class="text-2xl font-bold text-gray-900 text-center">Crear Cuenta de Administrador</h1>
    <p class="mt-2 text-sm text-gray-500 text-center">
        Esta será la cuenta maestra para la instalación de ZertixPOS. Asegúrate de usar un correo al que tengas acceso.
    </p>

    <form wire:submit.prevent="nextStep" class="mt-8 space-y-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nombre Completo</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <x-heroicon-s-user class="w-5 h-5" />
                </span>
                <input type="text" wire:model="adminName" placeholder="Ej. Juan Pérez"
                    class="w-full pl-11 pr-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
            </div>
            @error('adminName') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Correo Electrónico</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <x-heroicon-s-envelope class="w-5 h-5" />
                </span>
                <input type="email" wire:model="adminEmail" placeholder="admin@empresa.com"
                    class="w-full pl-11 pr-4 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
            </div>
            @error('adminEmail') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contraseña</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <x-heroicon-s-lock-closed class="w-5 h-5" />
                </span>
                <input :type="show ? 'text' : 'password'" wire:model="adminPassword" placeholder="Mínimo 8 caracteres"
                    class="w-full pl-11 pr-11 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                    <x-heroicon-s-eye-slash class="w-5 h-5" x-show="!show" />
                    <x-heroicon-s-eye class="w-5 h-5" x-show="show" x-cloak />
                </button>
            </div>
            @error('adminPassword') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar Contraseña</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <x-heroicon-s-lock-closed class="w-5 h-5" />
                </span>
                <input :type="show ? 'text' : 'password'" wire:model="adminPasswordConfirmation" placeholder="Repite tu contraseña"
                    class="w-full pl-11 pr-11 py-3 rounded-xl border-gray-200 focus:border-zertix-primary focus:ring-zertix-primary text-sm" />
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                    <x-heroicon-s-eye-slash class="w-5 h-5" x-show="!show" />
                    <x-heroicon-s-eye class="w-5 h-5" x-show="show" x-cloak />
                </button>
            </div>
            @error('adminPasswordConfirmation') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full bg-zertix-primary hover:bg-zertix-primary-dark text-white font-bold py-3.5 rounded-xl transition-colors flex items-center justify-center gap-2">
            Siguiente: Datos de Empresa
            <x-heroicon-s-arrow-right class="w-4 h-4" />
        </button>
    </form>

    <div class="mt-6 flex items-start gap-2.5 bg-gray-50 rounded-xl p-4">
        <x-heroicon-s-information-circle class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
        <p class="text-xs text-gray-500 leading-relaxed">
            La cuenta maestra tiene privilegios totales del sistema. Podrás crear roles adicionales y sub-usuarios una vez finalizada la configuración inicial.
        </p>
    </div>
</div>
