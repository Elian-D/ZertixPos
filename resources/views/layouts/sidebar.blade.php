{{-- SIDEBAR --}}
<x-sidebar.layout>

    <x-sidebar.item href="{{ route('dashboard') }}" icon="heroicon-s-home">
        Dashboard
    </x-sidebar.item>

    {{-- GRUPO 1: CRM (REQ-3.2). Cotizaciones migró de sales.quotes.*→clients.quotes.*
            (Fase 7.9, cuarta pasada: migración de rutas) — ya vive físicamente bajo
            app/clients/quotes, así que un solo comodín alcanza, sin listas de
            exclusión manuales. --}}
    <x-sidebar.dropdown id="clientes" icon="heroicon-s-user-group" label="CRM" :activeRoutes="['app/clients*']">
        {{-- :active explícito (REQ-7.9, séptima pasada): "Clientes" vive en la
                RAÍZ de app/clients, y sus 5 hermanos (Cotizaciones, Puntos de Reparto,
                Equipos, Tipos de Negocio, Tipos de Equipo) viven anidados bajo ese
                mismo prefijo — el comodín de respaldo del subitem (para seguir
                resaltado en /app/clients/5/editar) también los atrapaba a ellos,
                resaltando 2 subitems a la vez. routeIs('clients.*') sigue siendo
                amplio por la misma razón (los nombres de ruta también comparten
                prefijo), así que se excluyen explícitamente los 5 namespaces hermanos. --}}
        <x-sidebar.subitem href="{{ route('clients.index') }}"
            :active="request()->routeIs('clients.*') && ! request()->routeIs(['clients.quotes.*', 'clients.delivery_points.*', 'clients.equipment.*', 'clients.businessTypes.*', 'clients.equipmentTypes.*'])">
            Clientes
        </x-sidebar.subitem>
        @can('quotes.view')
            @if(module_enabled('sales.quotes'))
                <x-sidebar.subitem href="{{ route('clients.quotes.index') }}">Cotizaciones</x-sidebar.subitem>
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
    </x-sidebar.dropdown>

    {{-- GRUPO 2: Ventas (REQ-3.2) — un solo dropdown, colapsa "Ventas" + "Puntos de Venta" --}}
    @canany(['sales.view', 'pos_sessions.manage'])
        {{-- Dashboard Ventas (app/sales/dashboard→app/reports/sales) y Cotizaciones
                (app/sales/quotes→app/clients/quotes) ya no viven bajo app/sales* (Fase
                7.9, cuarta pasada) — un solo comodín simple alcanza de nuevo. --}}
        <x-sidebar.dropdown
            id="ventas"
            icon="heroicon-s-banknotes"
            label="Ventas"
            :activeRoutes="['app/sales*']"
        >
            @can('sales.view')
                {{-- :active explícito (REQ-7.9, séptima pasada): mismo problema que
                        "Clientes" — "Ventas" vive en la raíz de app/sales, y los 4
                        subitems de POS viven anidados bajo app/sales/pos*. --}}
                <x-sidebar.subitem href="{{ route('sales.index') }}"
                    :active="request()->routeIs('sales.*') && ! request()->routeIs('sales.pos.*')">
                    Ventas
                </x-sidebar.subitem>
            @endcan
            @can('pos_sessions.manage')
                <x-sidebar.subitem href="{{ route('sales.pos.index') }}">Punto de Venta</x-sidebar.subitem>
                <x-sidebar.subitem href="{{ route('sales.pos.settings.edit') }}">Configuración</x-sidebar.subitem>
                <x-sidebar.subitem href="{{ route('sales.pos.terminals.index') }}">Terminales</x-sidebar.subitem>
                <x-sidebar.subitem href="{{ route('sales.pos.sessions.index') }}">Turnos</x-sidebar.subitem>
                {{-- Movimientos de Caja: oculto, ver Fase 9.1 en docs/features/POS-Interfaz.md --}}
            @endcan
        </x-sidebar.dropdown>
    @endcanany

    {{-- GRUPO 3: Inventario (REQ-3.2) — Productos/Categorías/Unidades anidado.
            Dashboard Inventario migró a app/reports/inventory (Fase 7.9, cuarta
            pasada) — un solo comodín simple alcanza de nuevo. --}}
    <x-sidebar.dropdown
        id="inventario"
        icon="heroicon-s-cube"
        :label="module_enabled('inventory.tracking') ? 'Inventario' : 'Productos/Servicios'"
        :activeRoutes="['app/inventory*']"
    >
        @can('products.view')
            {{-- :active explícito (REQ-7.9, séptima pasada): mismo problema —
                    "Productos/Servicios" vive en la raíz de app/inventory/products, y
                    Categorías/Unidades viven anidadas bajo ese mismo prefijo. --}}
            <x-sidebar.subitem href="{{ route('inventory.products.index') }}"
                :active="request()->routeIs('inventory.products.*') && ! request()->routeIs(['inventory.products.categories.*', 'inventory.products.units.*'])">
                Productos/Servicios
            </x-sidebar.subitem>
            <x-sidebar.subitem href="{{ route('inventory.products.categories.index') }}">Categorías</x-sidebar.subitem>
            <x-sidebar.subitem href="{{ route('inventory.products.units.index') }}">Unidades de Medida</x-sidebar.subitem>
        @endcan
        @if (module_enabled('inventory.tracking'))
            @can('inventory_stocks.view')
                <x-sidebar.subitem href="{{ route('inventory.stocks.index') }}">Stock Actual</x-sidebar.subitem>
            @endcan
            @can('inventory_movements.view')
                <x-sidebar.subitem href="{{ route('inventory.movements.index') }}">Movimientos</x-sidebar.subitem>
            @endcan
            @can('warehouses.manage')
                <x-sidebar.subitem href="{{ route('inventory.warehouses.index') }}">Almacenes</x-sidebar.subitem>
            @endcan
        @endif
    </x-sidebar.dropdown>

    {{-- GRUPO 4: Finanzas (REQ-3.2, rename de "Contabilidad") — absorbe Facturas y NCF --}}
    @can('accounting.dashboard')
        {{-- Dashboard Finanzas (app/finance/dashboard) y Dashboard NCF
                (app/finance/ncf/dashboard) migraron a app/reports/finance y
                app/reports/ncf (Fase 7.9, cuarta pasada) — un solo comodín simple
                alcanza de nuevo. --}}
        <x-sidebar.dropdown
            id="finanzas"
            icon="heroicon-s-calculator"
            label="Finanzas"
            :activeRoutes="['app/finance*']"
        >
            <x-sidebar.subitem href="{{ route('finance.overview.index') }}">Ingresos y Gastos</x-sidebar.subitem>
            @if(module_enabled('sales.receivables'))
                <x-sidebar.subitem href="{{ route('finance.receivables.index') }}">Cuentas por Cobrar</x-sidebar.subitem>
                <x-sidebar.subitem href="{{ route('finance.collections.index') }}">Cobros</x-sidebar.subitem>
            @endif
            @if(module_enabled('accounting.advanced'))
                <x-sidebar.subitem href="{{ route('finance.journal_entries.index') }}">Asientos Contables</x-sidebar.subitem>
                <x-sidebar.subitem href="{{ route('finance.accounts.index') }}">Plan de Cuentas</x-sidebar.subitem>
            @endif
            @can('invoices.view')
                <x-sidebar.subitem href="{{ route('finance.invoices.index') }}">Facturas</x-sidebar.subitem>
            @endcan

            {{-- NCF (Fiscal) — movido de Configuración (REQ-3.2/3.4) --}}
            @if(module_enabled('sales.ncf'))
                @can('ncf_sequences.view')
                    <x-sidebar.subitem href="{{ route('finance.ncf.sequences.index') }}">Secuencias NCF</x-sidebar.subitem>
                    <x-sidebar.subitem href="{{ route('finance.ncf.logs.index') }}">Historial NCF</x-sidebar.subitem>
                    <x-sidebar.subitem href="{{ route('finance.ncf.types.index') }}">Tipos NCF</x-sidebar.subitem>
                @endcan
            @endif
        </x-sidebar.dropdown>
    @endcan

    {{-- GRUPO 5: Reportes (REQ-3.2, nuevo) — junta los 4 dashboards sueltos --}}
    @php
        $showReportes = auth()->user()->can('sales.view')
            || (module_enabled('inventory.tracking') && auth()->user()->can('inventory.dashboard'))
            || (module_enabled('sales.ncf') && auth()->user()->can('ncf_sequences.view'))
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
                <x-sidebar.subitem href="{{ route('reports.sales') }}">Reportes de Ventas</x-sidebar.subitem>
            @endcan
            @if(module_enabled('inventory.tracking'))
                @can('inventory.dashboard')
                    <x-sidebar.subitem href="{{ route('reports.inventory') }}">Reportes de Inventario</x-sidebar.subitem>
                @endcan
            @endif
            @if(module_enabled('sales.ncf'))
                @can('ncf_sequences.view')
                    <x-sidebar.subitem href="{{ route('reports.ncf') }}">Reportes de NCF</x-sidebar.subitem>
                @endcan
            @endif
            @if(module_enabled('accounting.advanced'))
                @can('accounting.dashboard')
                    <x-sidebar.subitem href="{{ route('reports.finance') }}">Reportes de Finanzas</x-sidebar.subitem>
                @endcan
            @endif
        </x-sidebar.dropdown>
    @endif

    {{-- GRUPO 6: Sistema (REQ-3.2) — se achica, ya sin NCF.
            Usuarios y Roles/Permisos migraron de app/users*/app/roles* a
            app/config/users*/app/config/roles* (Fase 7.9, cuarta pasada) — ya
            viven bajo el mismo prefijo que el resto del grupo, un solo comodín
            simple alcanza de nuevo. --}}
    <x-sidebar.dropdown
        id="configuracion"
        icon="heroicon-s-cog-6-tooth"
        label="Configuración"
        :activeRoutes="['app/config*']"
    >
        <x-sidebar.subitem href="{{ route('configuration.general.edit') }}">Configuración General</x-sidebar.subitem>
        <x-sidebar.subitem href="{{ route('config.users.index') }}">Usuarios</x-sidebar.subitem>
        <x-sidebar.subitem href="{{ route('config.roles.index') }}">Roles/Permisos</x-sidebar.subitem>
        @can('config.modules')
            <x-sidebar.subitem href="{{ route('configuration.features') }}">Funciones del Sistema</x-sidebar.subitem>
        @endcan
        <x-sidebar.subitem href="{{ route('configuration.catalogs.index') }}">Catálogos del Sistema</x-sidebar.subitem>
    </x-sidebar.dropdown>
</x-sidebar.layout>