{{-- SIDEBAR --}}
<x-sidebar.layout>

    {{-- DASHBOARD --}}
    @can('dashboard.view')
        <x-sidebar.item href="{{ route('dashboard') }}" icon="heroicon-s-home">
            Dashboard
        </x-sidebar.item>
    @endcan

    {{-- GRUPO 1: CRM --}}
    @canany([
        'clients.view',
        'quotes.view',
        'delivery_points.view',
        'equipment.view',
    ])
        <x-sidebar.dropdown
            id="clientes"
            icon="heroicon-s-user-group"
            label="CRM"
            :activeRoutes="['app/clients*']"
        >
            @can('clients.view')
                <x-sidebar.subitem
                    href="{{ route('clients.index') }}"
                    :active="request()->routeIs('clients.*') && ! request()->routeIs([
                        'clients.quotes.*',
                        'clients.delivery_points.*',
                        'clients.equipment.*'
                    ])"
                >
                    Clientes
                </x-sidebar.subitem>
            @endcan

            @can('quotes.view')
                @if(module_enabled('sales.quotes'))
                    <x-sidebar.subitem href="{{ route('clients.quotes.index') }}">
                        Cotizaciones
                    </x-sidebar.subitem>
                @endif
            @endcan

            @can('delivery_points.view')
                @if(module_enabled('sales.delivery_points'))
                    <x-sidebar.subitem href="{{ route('clients.delivery_points.index') }}">
                        Puntos de Reparto
                    </x-sidebar.subitem>
                @endif
            @endcan

            @can('equipment.view')
                @if(module_enabled('clients.field_assets'))
                    <x-sidebar.subitem href="{{ route('clients.equipment.index') }}">
                        Equipos
                    </x-sidebar.subitem>
                @endif
            @endcan
        </x-sidebar.dropdown>
    @endcanany

    {{-- GRUPO 2: Ventas / POS --}}
    @canany([
        'sales.view',
        'pos_sessions.manage',
        'pos_terminals.view',
        'pos_config.view',
        'pos_sessions.history',
    ])
        <x-sidebar.dropdown
            id="ventas"
            icon="heroicon-s-banknotes"
            label="Ventas"
            :activeRoutes="['app/sales*']"
        >
            @can('sales.view')
                <x-sidebar.subitem 
                    href="{{ route('sales.index') }}"
                    :active="request()->routeIs('sales.*') && ! request()->routeIs('sales.pos.*')"
                >
                    Ventas
                </x-sidebar.subitem>
            @endcan

            @can('pos_sessions.manage')
                <x-sidebar.subitem href="{{ route('sales.pos.index') }}">
                    Punto de Venta
                </x-sidebar.subitem>
            @endcan

            @can('pos_config.view')
                <x-sidebar.subitem href="{{ route('sales.pos.settings.edit') }}">
                    Configuración
                </x-sidebar.subitem>
            @endcan

            @can('pos_terminals.view')
                <x-sidebar.subitem href="{{ route('sales.pos.terminals.index') }}">
                    Terminales
                </x-sidebar.subitem>
            @endcan

            @canany(['pos_sessions.manage', 'pos_sessions.history'])
                <x-sidebar.subitem href="{{ route('sales.pos.sessions.index') }}">
                    Turnos
                </x-sidebar.subitem>
            @endcanany
        </x-sidebar.dropdown>
    @endcanany

    {{-- GRUPO 3: Inventario --}}
    @canany([
        'products.view',
        'inventory_stocks.view',
        'inventory_movements.view',
        'warehouses.manage',
    ])
        <x-sidebar.dropdown
            id="inventario"
            icon="heroicon-s-cube"
            :label="module_enabled('inventory.tracking') ? 'Inventario' : 'Productos/Servicios'"
            :activeRoutes="['app/inventory*']"
        >
            @can('products.view')
                <x-sidebar.subitem
                    href="{{ route('inventory.products.index') }}"
                    :active="request()->routeIs('inventory.products.*')"
                >
                    Productos/Servicios
                </x-sidebar.subitem>
            @endcan

            @if(module_enabled('inventory.tracking'))
                @can('inventory_stocks.view')
                    <x-sidebar.subitem href="{{ route('inventory.stocks.index') }}">
                        Stock Actual
                    </x-sidebar.subitem>
                @endcan

                @can('inventory_movements.view')
                    <x-sidebar.subitem href="{{ route('inventory.movements.index') }}">
                        Movimientos
                    </x-sidebar.subitem>
                @endcan

                @can('warehouses.manage')
                    <x-sidebar.subitem href="{{ route('inventory.warehouses.index') }}">
                        Almacenes
                    </x-sidebar.subitem>
                @endcan
            @endif
        </x-sidebar.dropdown>
    @endcanany

    {{-- GRUPO 4: Finanzas --}}
    @canany([
        'accounting.dashboard',
        'receivables.view',
        'collections.view',
        'invoices.view',
        'ncf_sequences.view',
    ])
        <x-sidebar.dropdown
            id="finanzas"
            icon="heroicon-s-calculator"
            label="Finanzas"
            :activeRoutes="['app/finance*']"
        >
            @can('accounting.dashboard')
                <x-sidebar.subitem href="{{ route('finance.overview.index') }}">
                    Ingresos y Gastos
                </x-sidebar.subitem>
            @endcan

            @if(module_enabled('sales.receivables'))
                @can('receivables.view')
                    <x-sidebar.subitem href="{{ route('finance.receivables.index') }}">
                        Cuentas por Cobrar
                    </x-sidebar.subitem>
                @endcan

                @can('collections.view')
                    <x-sidebar.subitem href="{{ route('finance.collections.index') }}">
                        Cobros
                    </x-sidebar.subitem>
                @endcan
            @endif

            @if(module_enabled('accounting.advanced'))
                @can('accounting.dashboard')
                    <x-sidebar.subitem href="{{ route('finance.journal_entries.index') }}">
                        Asientos Contables
                    </x-sidebar.subitem>

                    <x-sidebar.subitem href="{{ route('finance.accounts.index') }}">
                        Plan de Cuentas
                    </x-sidebar.subitem>
                @endcan
            @endif

            @can('invoices.view')
                <x-sidebar.subitem href="{{ route('finance.invoices.index') }}">
                    Facturas
                </x-sidebar.subitem>
            @endcan

            @if(module_enabled('sales.ncf'))
                @can('ncf_sequences.view')
                    <x-sidebar.subitem href="{{ route('finance.ncf.sequences.index') }}">
                        Secuencias NCF
                    </x-sidebar.subitem>

                    <x-sidebar.subitem href="{{ route('finance.ncf.logs.index') }}">
                        Historial NCF
                    </x-sidebar.subitem>
                @endcan
            @endif
        </x-sidebar.dropdown>
    @endcanany

    {{-- GRUPO 5: Reportes --}}
    @php
        $showReportes = auth()->user()->can('sales.view')
            || (module_enabled('inventory.tracking') && auth()->user()->can('inventory.dashboard'))
            || (module_enabled('sales.ncf') && auth()->user()->can('ncf.reports'))
            || (module_enabled('accounting.advanced') && auth()->user()->can('accounting.dashboard'));
    @endphp

    @if($showReportes)
        <x-sidebar.dropdown
            id="reportes"
            icon="heroicon-s-chart-bar"
            label="Reportes"
            :activeRoutes="['app/reports*']"
        >
            @can('sales.view')
                <x-sidebar.subitem href="{{ route('reports.sales') }}">
                    Reportes de Ventas
                </x-sidebar.subitem>
            @endcan

            @if(module_enabled('inventory.tracking'))
                @can('inventory.dashboard')
                    <x-sidebar.subitem href="{{ route('reports.inventory') }}">
                        Reportes de Inventario
                    </x-sidebar.subitem>
                @endcan
            @endif

            @if(module_enabled('sales.ncf'))
                @can('ncf.reports')
                    <x-sidebar.subitem href="{{ route('reports.ncf') }}">
                        Reportes de NCF
                    </x-sidebar.subitem>
                @endcan
            @endif

            @if(module_enabled('accounting.advanced'))
                @can('accounting.dashboard')
                    <x-sidebar.subitem href="{{ route('reports.finance') }}">
                        Reportes de Finanzas
                    </x-sidebar.subitem>
                @endcan
            @endif
        </x-sidebar.dropdown>
    @endif

    {{-- GRUPO 6: Configuración — Centro de Configuración unificado (REQ-7.4),
         reemplaza el dropdown de links sueltos por un único punto de entrada.
         Mismo OR de permisos que antes decidía si el dropdown aparecía; cada
         tarjeta del hub (resources/views/configuration/index.blade.php) sigue
         su propio @can, así que perder acceso a un permiso puntual no oculta
         el botón entero, solo esa tarjeta. `config.view` (gateaba el viejo
         mini-hub de Catálogos, absorbido acá) queda fuera de esta lista —
         permiso huérfano, no borrado del seeder por si algún rol ya lo tiene
         asignado, pero ya no controla nada real. --}}
    @canany([
        'config.general',
        'config.payment_types',
        'config.billing',
        'ncf_types.manage',
        'document_types.view',
        'categories.manage',
        'units.manage',
        'business_types.manage',
        'equipment_types.manage',
        'users.view',
        'roles.view',
        'config.modules',
    ])
        <x-sidebar.item href="{{ route('configuration.index') }}" icon="heroicon-s-cog-6-tooth">
            Configuración
        </x-sidebar.item>
    @endcanany

</x-sidebar.layout>