<x-config-layout>

    <x-ui.toasts />
    
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-gray-800">
            Panel de Configuración
        </h1>
        <p class="text-gray-500 mt-2">
            Administra las configuraciones generales del sistema
        </p>
    </div>

    <!-- CARD PRINCIPAL -->
    <div class="mb-8">
        <a href="{{ route('configuration.general.edit') }}"
           class="group block bg-gradient-to-r from-indigo-600 to-violet-600
                  text-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all">

            <div class="flex items-center gap-6">
                <div class="p-4 rounded-xl bg-white/20">
                    <x-heroicon-s-adjustments-horizontal class="w-10 h-10" />
                </div>

                <div>
                    <h2 class="text-2xl font-bold">
                        Configuración general
                    </h2>
                    <p class="mt-1 text-indigo-100">
                        Información principal y parámetros globales del sistema
                    </p>
                </div>
            </div>

            <p class="mt-6 text-sm text-indigo-100 max-w-3xl">
                Define los datos base del sistema como información de la empresa,
                datos fiscales, contacto, monedas, zonas horarias y
                configuraciones esenciales para el funcionamiento general.
            </p>
        </a>
    </div>

    <!-- CARDS SECUNDARIAS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Estados de clientes -->
        <a href="{{ route('configuration.estados.index') }}" class="group bg-white border border-gray-200 p-6 rounded-xl shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-lg bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                    <x-heroicon-s-user class="w-6 h-6" />
                </div>
                <h2 class="text-lg font-semibold text-gray-800">Estados de clientes</h2>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                Define y administra los estados de los clientes.
            </p>
        </a>

        <!-- Días de la semana -->
        <a href="{{ route('configuration.dias.index') }}" class="group bg-white border border-gray-200 p-6 rounded-xl shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-lg bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                    <x-heroicon-s-calendar-days class="w-6 h-6" />
                </div>
                <h2 class="text-lg font-semibold text-gray-800">Días de la semana</h2>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                Configura los estados y disponibilidad de los días.
            </p>
        </a>

        <!-- Tipos de pago -->
        <a href="{{ route('configuration.pagos.index') }}" class="group bg-white border border-gray-200 p-6 rounded-xl shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-lg bg-sky-100 text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition">
                    <x-heroicon-s-currency-dollar class="w-6 h-6" />
                </div>
                <h2 class="text-lg font-semibold text-gray-800">Tipos de pago</h2>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                Administra los métodos y formas de pago del sistema.
            </p>
        </a>

    </div>
</x-config-layout>
