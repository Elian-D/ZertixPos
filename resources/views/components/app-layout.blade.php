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
        <div class="flex min-h-screen bg-gray-100"> 
            
            {{-- SIDEBAR --}}
            <x-sidebar.layout>
                
                <x-sidebar.item href="/dashboard" icon="heroicon-s-home">
                    Dashboard
                </x-sidebar.item>

                {{-- GRUPO 1: Operaciones --}}
                <x-sidebar.group>
                    <x-sidebar.title>Operaciones</x-sidebar.title>

                    {{-- Ventas (solo operaciones diarias) --}}
                    @canany(['view sales', 'view invoices'])
                        <x-sidebar.dropdown 
                            id="ventas" 
                            icon="heroicon-s-banknotes" 
                            :activeRoutes="['admin/sales*']"
                        >
                            Ventas
                            <x-slot name="submenu">
                                @can('view sales')
                                <x-sidebar.subitem href="/admin/sales/dashboard">Dashboard Ventas</x-sidebar.subitem>
                                    <x-sidebar.subitem href="/admin/sales">Punto de Venta (POS)</x-sidebar.subitem>
                                @endcan
                                @can('view invoices')
                                    <x-sidebar.subitem href="/admin/sales/invoice">Facturas</x-sidebar.subitem>
                                @endcan
                            </x-slot>
                        </x-sidebar.dropdown>
                    @endcanany

                    @can('view quotes')
                        <x-sidebar.item href="/admin/sales/quotes" icon="heroicon-s-document-currency-dollar">
                            Cotizaciones
                        </x-sidebar.item>
                    @endcan

                    <x-sidebar.item href="/rutas" icon="heroicon-s-map">
                        Rutas y Entregas
                    </x-sidebar.item>

                    @can('view inventory dashboard')
                        <x-sidebar.dropdown 
                            id="inventario" 
                            icon="heroicon-s-cube" 
                            :activeRoutes="['admin/inventory*']"
                        >
                            Inventario
                            <x-slot name="submenu">
                                <x-sidebar.subitem href="/admin/inventory/dashboard">Dashboard</x-sidebar.subitem>
                                <x-sidebar.subitem href="/admin/inventory/stocks">Stock Actual</x-sidebar.subitem>
                                <x-sidebar.subitem href="/admin/inventory/movements">Movimientos</x-sidebar.subitem>
                                <x-sidebar.subitem href="/admin/inventory/warehouses">Almacenes</x-sidebar.subitem>
                            </x-slot>
                        </x-sidebar.dropdown>
                    @endcan
                </x-sidebar.group>

                {{-- GRUPO 2: Finanzas --}}
                @can('view accounting dashboard')
                    <x-sidebar.group>
                        <x-sidebar.title>Finanzas</x-sidebar.title>

                        <x-sidebar.dropdown 
                            id="contabilidad" 
                            icon="heroicon-s-calculator" 
                            :activeRoutes="['admin/accounting*']"
                        >
                            Contabilidad
                            <x-slot name="submenu">
                                <x-sidebar.subitem href="/admin/accounting/overview">Ingresos y Gastos</x-sidebar.subitem>
                                @if(module_enabled('accounting.advanced'))
                                    <x-sidebar.subitem href="/admin/accounting/dashboard">Dashboard Contable</x-sidebar.subitem>
                                @endif
                                <x-sidebar.subitem href="/admin/accounting/receivables">Cuentas por Cobrar</x-sidebar.subitem>
                                <x-sidebar.subitem href="/admin/accounting/payments">Pagos</x-sidebar.subitem>
                                <x-sidebar.subitem href="/admin/accounting/document_types">Tipos de Documento</x-sidebar.subitem>
                                @if(module_enabled('accounting.advanced'))
                                    <div class="h-px bg-gray-700/30 my-1.5"></div>
                                    <x-sidebar.subitem href="/admin/accounting/journal_entries">Asientos Contables</x-sidebar.subitem>
                                    <x-sidebar.subitem href="/admin/accounting/accounts">Plan de Cuentas</x-sidebar.subitem>
                                @endif
                            </x-slot>
                        </x-sidebar.dropdown>
                    </x-sidebar.group>
                @endcan

                {{-- GRUPO 3: Catálogos --}}
                <x-sidebar.group>
                    <x-sidebar.title>Catálogos</x-sidebar.title>

                    <x-sidebar.dropdown id="productos" icon="heroicon-s-shopping-cart" :activeRoutes="['admin/products*']">
                        Productos/Servicios
                        <x-slot name="submenu">
                            <x-sidebar.subitem href="/admin/products">Productos/Servicios</x-sidebar.subitem>
                            <x-sidebar.subitem href="/admin/products/categories">Categorías</x-sidebar.subitem>
                            <x-sidebar.subitem href="/admin/products/units">Unidades de Medida</x-sidebar.subitem>
                        </x-slot>
                    </x-sidebar.dropdown>

                    <x-sidebar.dropdown id="clientes" icon="heroicon-s-user-group" :activeRoutes="['admin/clients*']">
                        Clientes
                        <x-slot name="submenu">
                            <x-sidebar.subitem href="/admin/clients">Lista de Clientes</x-sidebar.subitem>
                            @if(module_enabled('sales.delivery_points'))
                                <x-sidebar.subitem href="/admin/clients/pos">Puntos de Venta</x-sidebar.subitem>
                            @endif
                            @if(module_enabled('clients.field_assets'))
                                <x-sidebar.subitem href="/admin/clients/equipments">Equipos</x-sidebar.subitem>
                            @endif
                            @if(module_enabled('sales.delivery_points') || module_enabled('clients.field_assets'))
                                <div class="h-px bg-gray-700/30 my-1.5"></div>
                            @endif
                            @if(module_enabled('sales.delivery_points'))
                                <x-sidebar.subitem href="/admin/clients/businessTypes">Tipos de Negocio</x-sidebar.subitem>
                            @endif
                            @if(module_enabled('clients.field_assets'))
                                <x-sidebar.subitem href="/admin/clients/equipmentTypes">Tipos de Equipo</x-sidebar.subitem>
                            @endif
                        </x-slot>
                    </x-sidebar.dropdown>

                    <x-sidebar.dropdown id="pos" icon="heroicon-s-building-storefront" :activeRoutes="['admin/pos*']">
                        Puntos de Venta
                        <x-slot name="submenu">
                            <x-sidebar.subitem href="{{ route('sales.pos.index') }}">Ir al POS</x-sidebar.subitem>
                            <div class="h-px bg-gray-700/30 my-1.5"></div>
                            <x-sidebar.subitem href="/admin/sales/pos/settings">Configuración</x-sidebar.subitem>
                            <x-sidebar.subitem href="/admin/sales/pos/terminals">Terminales</x-sidebar.subitem>
                            <x-sidebar.subitem href="/admin/sales/pos/sessions">Turnos</x-sidebar.subitem>
                            {{-- Movimientos de Caja: oculto, ver Fase 9.1 en docs/features/POS-Interfaz.md --}}
                            {{-- <x-sidebar.subitem href="/admin/sales/pos/cash-movements">Movimientos de Caja</x-sidebar.subitem> --}}
                        </x-slot>
                    </x-sidebar.dropdown>
                </x-sidebar.group>

                {{-- GRUPO 4: Sistema --}}
                <x-sidebar.group>
                    <x-sidebar.title>Sistema</x-sidebar.title>

                    <x-sidebar.item href="/admin/users" icon="heroicon-s-users">
                        Usuarios
                    </x-sidebar.item>

                    <x-sidebar.item href="/admin/roles" icon="heroicon-s-lock-closed">
                        Roles
                    </x-sidebar.item>

                    @php
                        $config = \App\Models\Configuration\ConfiguracionGeneral::actual();
                    @endphp

                    <x-sidebar.dropdown 
                        id="configuracion" 
                        icon="heroicon-s-cog-6-tooth" 
                        :activeRoutes="['admin/config*', 'admin/sales/ncf*']"
                    >
                        Configuración
                        <x-slot name="submenu">
                            <x-sidebar.subitem href="/admin/config">General</x-sidebar.subitem>
                            
                            {{-- Solo mostrar si la empresa usa NCF --}}
                            @if($config->usa_ncf)
                                @can('view ncf sequences')
                                    <div class="h-px bg-gray-700/30 my-1.5"></div>
                                    <div class="px-3 py-1 text-[10px] font-bold text-gray-500 uppercase tracking-wider">NCF (Fiscal)</div>
                                    <x-sidebar.subitem href="/admin/sales/ncf/dashboard">Dashboard NCF</x-sidebar.subitem>
                                    <x-sidebar.subitem href="/admin/sales/ncf/sequences">Secuencias NCF</x-sidebar.subitem>
                                    <x-sidebar.subitem href="/admin/sales/ncf/logs">Historial NCF</x-sidebar.subitem>
                                    <x-sidebar.subitem href="/admin/sales/ncf/types">Tipos NCF</x-sidebar.subitem>
                                @endcan
                            @endif
                        </x-slot>
                    </x-sidebar.dropdown>
                </x-sidebar.group>

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