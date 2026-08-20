<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        {{-- 1. Directiva de Estilos de Livewire --}}
            @livewireStyles

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    {{-- Corregido validación del tamaño, para mobiles cerardo y para pc abierto --}}
    <body 
        class="font-sans antialiased" 
        x-data="{
            isSidebarOpen: false,
            isHovered: false,
            updateSidebarState() {
                this.isSidebarOpen = window.innerWidth >= 640;
                // Avisar a los gráficos que el tamaño cambió
                window.dispatchEvent(new Event('resize-charts'));
            }
        }"
        {{-- Agregamos un watch para disparar el evento cada vez que isSidebarOpen cambie manualmente --}}
        x-init="
            updateSidebarState();
            window.addEventListener('resize', () => updateSidebarState());
            $watch('isSidebarOpen', () => {
                // Esperamos un poco a que termine la animación del CSS
                setTimeout(() => window.dispatchEvent(new Event('resize-charts')), 310);
            });
        "
        
    >
            
        {{-- ELIMINADA la clase ml-XX para que el sidebar FLOTE cuando esté colapsado. --}}
        {{-- x-cloak acá evita que se pinte el estado intermedio (sidebar ya visible, --}}
        {{-- contenido todavía sin el margen aplicado) mientras Alpine calcula isSidebarOpen. --}}
        <div class="flex min-h-screen bg-gray-100" x-cloak>
            
            {{-- SIDEBAR --}}
            <x-sidebar.layout>

                <x-sidebar.item href="{{ route('dashboard') }}" icon="heroicon-s-home">
                    Dashboard
                </x-sidebar.item>

                {{-- GRUPO 1: CRM (REQ-3.2) --}}
                <x-sidebar.dropdown id="clientes" icon="heroicon-s-user-group" :activeRoutes="['app/clients*']">
                    CRM
                    <x-slot name="submenu">
                        <x-sidebar.subitem href="{{ route('clients.index') }}">Clientes</x-sidebar.subitem>
                        @can('view quotes')
                            @if(module_enabled('sales.quotes'))
                                <x-sidebar.subitem href="{{ route('sales.quotes.index') }}">Cotizaciones</x-sidebar.subitem>
                            @endif
                        @endcan
                        @if(module_enabled('sales.delivery_points'))
                            <x-sidebar.subitem href="{{ route('clients.delivery_points.index') }}">Puntos de Reparto</x-sidebar.subitem>
                        @endif
                        @if(module_enabled('clients.field_assets'))
                            <x-sidebar.subitem href="{{ route('clients.equipment.index') }}">Equipos</x-sidebar.subitem>
                        @endif
                        @if(module_enabled('sales.delivery_points'))
                            <x-sidebar.subitem href="{{ route('clients.businessTypes.index') }}">Tipos de Negocio</x-sidebar.subitem>
                        @endif
                        @if(module_enabled('clients.field_assets'))
                            <x-sidebar.subitem href="{{ route('clients.equipmentTypes.index') }}">Tipos de Equipo</x-sidebar.subitem>
                        @endif
                    </x-slot>
                </x-sidebar.dropdown>

                {{-- GRUPO 2: Ventas (REQ-3.2) — un solo dropdown, colapsa "Ventas" + "Puntos de Venta" --}}
                @canany(['view sales', 'pos sessions manage'])

                    <x-sidebar.dropdown
                        id="ventas"
                        icon="heroicon-s-banknotes"
                        :activeRoutes="['app/sales*']"
                    >
                        Ventas
                        <x-slot name="submenu">
                            @can('view sales')
                                <x-sidebar.subitem href="{{ route('sales.index') }}">Ventas</x-sidebar.subitem>
                            @endcan
                            @can('pos sessions manage')
                                <x-sidebar.subitem href="{{ route('sales.pos.index') }}">Punto de Venta</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('sales.pos.settings.edit') }}">Configuración</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('sales.pos.terminals.index') }}">Terminales</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('sales.pos.sessions.index') }}">Turnos</x-sidebar.subitem>
                                {{-- Movimientos de Caja: oculto, ver Fase 9.1 en docs/features/POS-Interfaz.md --}}
                            @endcan
                        </x-slot>
                    </x-sidebar.dropdown>

                @endcanany

                {{-- GRUPO 3: Inventario (REQ-3.2) — Productos/Categorías/Unidades anidado --}}
                    <x-sidebar.dropdown
                        id="inventario"
                        icon="heroicon-s-cube"
                        :activeRoutes="['app/inventory*']"
                    >
                        @if (module_enabled('inventory.tracking'))
                            Inventario
                        @else
                            Productos/Servicios
                        @endif
                        <x-slot name="submenu">
                            @can('view products')
                                <x-sidebar.subitem href="{{ route('inventory.products.index') }}">Productos/Servicios</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('inventory.products.categories.index') }}">Categorías</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('inventory.products.units.index') }}">Unidades de Medida</x-sidebar.subitem>
                            @endcan
                            @if (module_enabled('inventory.tracking'))
                            @can('inventory stocks index')
                                    <x-sidebar.subitem href="{{ route('inventory.stocks.index') }}">Stock Actual</x-sidebar.subitem>
                                @endcan
                                @can('view inventory movements')
                                    <x-sidebar.subitem href="{{ route('inventory.movements.index') }}">Movimientos</x-sidebar.subitem>
                                @endcan
                                @can('configure warehouses')
                                    <x-sidebar.subitem href="{{ route('inventory.warehouses.index') }}">Almacenes</x-sidebar.subitem>
                                @endcan
                            @endif

                        </x-slot>
                    </x-sidebar.dropdown>

                {{-- GRUPO 4: Finanzas (REQ-3.2, rename de "Contabilidad") — absorbe Facturas y NCF --}}
                @can('view accounting dashboard')
                    <x-sidebar.dropdown
                        id="finanzas"
                        icon="heroicon-s-calculator"
                        :activeRoutes="['app/finance*']"
                    >
                        Finanzas
                        <x-slot name="submenu">
                            <x-sidebar.subitem href="{{ route('finance.overview.index') }}">Ingresos y Gastos</x-sidebar.subitem>
                            @if(module_enabled('sales.receivables'))
                                <x-sidebar.subitem href="{{ route('finance.receivables.index') }}">Cuentas por Cobrar</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('finance.collections.index') }}">Cobros</x-sidebar.subitem>
                            @endif
                            @if(module_enabled('accounting.advanced'))
                                <x-sidebar.subitem href="{{ route('finance.journal_entries.index') }}">Asientos Contables</x-sidebar.subitem>
                                <x-sidebar.subitem href="{{ route('finance.accounts.index') }}">Plan de Cuentas</x-sidebar.subitem>
                            @endif
                            @can('view invoices')
                                <x-sidebar.subitem href="{{ route('finance.invoices.index') }}">Facturas</x-sidebar.subitem>
                            @endcan

                            {{-- NCF (Fiscal) — movido de Configuración (REQ-3.2/3.4) --}}
                            @if(module_enabled('sales.ncf'))
                                @can('view ncf sequences')
                                    <x-sidebar.subitem href="{{ route('finance.ncf.sequences.index') }}">Secuencias NCF</x-sidebar.subitem>
                                    <x-sidebar.subitem href="{{ route('finance.ncf.logs.index') }}">Historial NCF</x-sidebar.subitem>
                                    <x-sidebar.subitem href="{{ route('finance.ncf.types.index') }}">Tipos NCF</x-sidebar.subitem>
                                @endcan
                            @endif
                        </x-slot>
                    </x-sidebar.dropdown>
                @endcan

                {{-- GRUPO 5: Reportes (REQ-3.2, nuevo) — junta los 4 dashboards sueltos --}}
                @php
                    $showReportes = auth()->user()->can('view sales')
                        || (module_enabled('inventory.tracking') && auth()->user()->can('view inventory dashboard'))
                        || (module_enabled('sales.ncf') && auth()->user()->can('view ncf sequences'))
                        || (module_enabled('accounting.advanced') && auth()->user()->can('view accounting dashboard'));
                @endphp
                @if($showReportes)
                    <x-sidebar.dropdown
                        id="reportes"
                        icon="heroicon-s-chart-bar"
                        :activeRoutes="['app/sales/dashboard', 'app/inventory/dashboard', 'app/finance/ncf/dashboard', 'app/finance/dashboard']"
                    >
                        Reportes
                        <x-slot name="submenu">
                            @can('view sales')
                                <x-sidebar.subitem href="{{ route('sales.dashboard') }}">Dashboard Ventas</x-sidebar.subitem>
                            @endcan
                            @if(module_enabled('inventory.tracking'))
                                @can('view inventory dashboard')
                                    <x-sidebar.subitem href="{{ route('inventory.dashboard.index') }}">Dashboard Inventario</x-sidebar.subitem>
                                @endcan
                            @endif
                            @if(module_enabled('sales.ncf'))
                                @can('view ncf sequences')
                                    <x-sidebar.subitem href="{{ route('finance.ncf.dashboard') }}">Dashboard NCF</x-sidebar.subitem>
                                @endcan
                            @endif
                            @if(module_enabled('accounting.advanced'))
                                @can('view accounting dashboard')
                                    <x-sidebar.subitem href="{{ route('finance.dashboard.index') }}">Dashboard Finanzas</x-sidebar.subitem>
                                @endcan
                            @endif
                        </x-slot>
                    </x-sidebar.dropdown>
                @endif

                {{-- GRUPO 6: Sistema (REQ-3.2) — se achica, ya sin NCF --}}
                <x-sidebar.dropdown
                    id="configuracion"
                    icon="heroicon-s-cog-6-tooth"
                    :activeRoutes="['app/config*']"
                >
                    Configuración
                    <x-slot name="submenu">
                        <x-sidebar.subitem href="{{ route('configuration.general.edit') }}">Configuración General</x-sidebar.subitem>
                        <x-sidebar.subitem href="{{ route('users.index') }}">Usuarios</x-sidebar.subitem>
                        <x-sidebar.subitem href="{{ route('roles.index') }}">Roles/Permisos</x-sidebar.subitem>
                        @can('configure system modules')
                            <x-sidebar.subitem href="{{ route('configuration.features') }}">Funcionalidades del Sistema</x-sidebar.subitem>
                        @endcan
                        <x-sidebar.subitem href="{{ route('configuration.catalogs.index') }}">Catálogos del Sistema</x-sidebar.subitem>
                    </x-slot>
                </x-sidebar.dropdown>
            </x-sidebar.layout>
                        
            {{-- OVERLAY (Fondo oscuro para móviles) --}}
            {{-- Se activa si el sidebar está abierto Y es móvil (ancho < 640px) --}}
            <div x-show="isSidebarOpen && (window.innerWidth < 640)" 
                 @click="isSidebarOpen = false" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition ease-in duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-black bg-opacity-50 z-40 sm:hidden">
            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="flex-1 flex flex-col min-w-0 transition-all duration-300 ml-0"
                :class="{
                    'sm:ml-64': isSidebarOpen,
                    'sm:ml-20': !isSidebarOpen
                }">

                {{-- HEADER / TOPBAR --}}
                <x-header />
                
                {{-- CONTENIDO VARIABLE --}}
                {{-- AÑADIDO: Se añade el margen solo en PC y lo controla x-data para compensar el sidebar. --}}
                <main class="p-6 transition-all duration-300 w-full">
                    {{ $slot }}
                </main>

            </div>
        </div>
        @stack('scripts')
        
        {{-- Usamos la configuración manual para que no inyecte Alpine doble --}}
        @livewireScriptConfig
    </body>
</html>