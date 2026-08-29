<?php

/**
 * Traducciones de permisos para las pantallas de Crear/Editar Rol y
 * Crear/Editar Usuario (`resources/views/roles/{create,edit}.blade.php`,
 * `resources/views/users/{create,edit}.blade.php`, vía el partial compartido
 * `resources/views/partials/permission-groups.blade.php`), consumidas por el
 * helper `trans_permission()` (REQ-2.6). La estructura debe ir anidada
 * por segmento de punto (`recurso` -> `accion` -> label/description),
 * NO como key plana `'recurso.accion'`, porque Laravel resuelve
 * `permissions.sales.create.label` recorriendo niveles anidados del
 * array, no comparando una key literal con puntos.
 *
 * Mantener en sincronía con `database/seeders/AppInit/PermissionSeeder.php`
 * cuando se agregue/renombre/elimine un permiso ahí.
 *
 * `pos_cash_movements.create` NO tiene entrada acá a propósito (REQ-2.2):
 * su ruta está comentada (dormida) y se filtra explícito en
 * `PermissionGroup::groupedForAssignment()` para que no aparezca ni se pueda
 * asignar mientras siga apagada.
 *
 * Labels con el recurso incluido (2026-08-28) — "Ver"/"Crear"/"Editar"/
 * "Eliminar" a secas, repetido en 8 tarjetas de grupo, no dice qué se está
 * viendo/creando sin leer el título de la tarjeta. Cada label nombra el
 * recurso explícito (ej. "Gestionar tipos de negocio", no "Gestionar").
 */
return [

    'dashboard' => [
        'view' => ['label' => 'Ver panel principal', 'description' => 'Ver el panel principal (Dashboard) del sistema.'],
    ],

    'roles' => [
        'view' => ['label' => 'Ver roles', 'description' => 'Ver el listado de roles y sus permisos.'],
        'create' => ['label' => 'Crear roles', 'description' => 'Crear nuevos roles.'],
        'edit' => ['label' => 'Editar roles', 'description' => 'Editar roles existentes y sus permisos asignados.'],
        'delete' => ['label' => 'Eliminar roles', 'description' => 'Eliminar roles existentes.'],
    ],

    'users' => [
        'view' => ['label' => 'Ver usuarios', 'description' => 'Ver el listado de usuarios del sistema.'],
        'create' => ['label' => 'Crear usuarios', 'description' => 'Crear nuevos usuarios.'],
        'edit' => ['label' => 'Editar usuarios', 'description' => 'Editar la información de usuarios existentes.'],
        'delete' => ['label' => 'Eliminar usuarios', 'description' => 'Eliminar usuarios existentes.'],
        'assign' => ['label' => 'Cambiar rol y permisos extra', 'description' => 'Cambiar el rol o los permisos adicionales de un usuario existente.'],
    ],

    'config' => [
        'view' => ['label' => 'Ver configuración general', 'description' => 'Ver la configuración general del sistema.'],
        'general' => ['label' => 'Editar configuración general', 'description' => 'Editar la configuración general (moneda, logo, impuestos, etc.).'],
        'payment_types' => ['label' => 'Gestionar tipos de pago', 'description' => 'Gestionar los tipos de pago disponibles en el sistema.'],
        'modules' => ['label' => 'Gestionar módulos del sistema', 'description' => 'Activar o desactivar módulos opcionales del sistema.'],
    ],

    'business_types' => [
        'manage' => ['label' => 'Gestionar tipos de negocio', 'description' => 'Crear, editar y eliminar tipos de negocio.'],
    ],

    'equipment_types' => [
        'manage' => ['label' => 'Gestionar tipos de equipo', 'description' => 'Crear, editar y eliminar tipos de equipo.'],
    ],

    'clients' => [
        'view' => ['label' => 'Ver clientes', 'description' => 'Ver el listado de clientes.'],
        'create' => ['label' => 'Crear clientes', 'description' => 'Registrar nuevos clientes.'],
        'edit' => ['label' => 'Editar clientes', 'description' => 'Editar la información de clientes existentes.'],
        'delete' => ['label' => 'Eliminar clientes', 'description' => 'Eliminar (enviar a papelera) clientes existentes.'],
        'restore' => ['label' => 'Restaurar clientes', 'description' => 'Restaurar clientes desde la papelera.'],
    ],

    'delivery_points' => [
        'view' => ['label' => 'Ver puntos de reparto', 'description' => 'Ver el listado de puntos de reparto.'],
        'create' => ['label' => 'Crear puntos de reparto', 'description' => 'Registrar nuevos puntos de reparto.'],
        'edit' => ['label' => 'Editar puntos de reparto', 'description' => 'Editar puntos de reparto existentes.'],
        'delete' => ['label' => 'Eliminar puntos de reparto', 'description' => 'Eliminar (enviar a papelera) puntos de reparto.'],
        'restore' => ['label' => 'Restaurar puntos de reparto', 'description' => 'Restaurar puntos de reparto desde la papelera.'],
    ],

    'equipment' => [
        'view' => ['label' => 'Ver equipos', 'description' => 'Ver el listado de equipos.'],
        'create' => ['label' => 'Crear equipos', 'description' => 'Registrar nuevos equipos.'],
        'edit' => ['label' => 'Editar equipos', 'description' => 'Editar equipos existentes.'],
        'delete' => ['label' => 'Eliminar equipos', 'description' => 'Eliminar (enviar a papelera) equipos.'],
        'restore' => ['label' => 'Restaurar equipos', 'description' => 'Restaurar equipos desde la papelera.'],
    ],

    'categories' => [
        'manage' => ['label' => 'Gestionar categorías', 'description' => 'Crear, editar y eliminar categorías de productos.'],
    ],

    'units' => [
        'manage' => ['label' => 'Gestionar unidades de medida', 'description' => 'Crear, editar y eliminar unidades de medida.'],
    ],

    'products' => [
        'view' => ['label' => 'Ver productos', 'description' => 'Ver el listado de productos.'],
        'create' => ['label' => 'Crear productos', 'description' => 'Registrar nuevos productos.'],
        'edit' => ['label' => 'Editar productos', 'description' => 'Editar productos existentes.'],
        'delete' => ['label' => 'Eliminar productos', 'description' => 'Eliminar (enviar a papelera) productos.'],
        'restore' => ['label' => 'Restaurar productos', 'description' => 'Restaurar productos desde la papelera.'],
    ],

    'warehouses' => [
        'manage' => ['label' => 'Gestionar almacenes', 'description' => 'Crear, editar y eliminar almacenes.'],
    ],

    'inventory' => [
        'dashboard' => ['label' => 'Ver panel de inventario', 'description' => 'Ver el panel general de inventario.'],
    ],

    'inventory_stocks' => [
        'view' => ['label' => 'Ver stock actual', 'description' => 'Ver el stock actual de productos.'],
        'update' => ['label' => 'Actualizar stock', 'description' => 'Ajustar manualmente el stock de productos.'],
        'export' => ['label' => 'Exportar stock', 'description' => 'Exportar el stock actual a Excel.'],
    ],

    'inventory_movements' => [
        'view' => ['label' => 'Ver movimientos de inventario', 'description' => 'Ver el historial de movimientos de inventario.'],
        'create_adjustment' => ['label' => 'Crear ajuste de inventario', 'description' => 'Registrar un movimiento de ajuste de inventario.'],
    ],

    'accounting' => [
        'dashboard' => ['label' => 'Ver panel de contabilidad', 'description' => 'Ver el panel de contabilidad e ingresos y gastos.'],
    ],

    'document_types' => [
        'view' => ['label' => 'Ver tipos de documento', 'description' => 'Ver el listado de tipos de documento contable.'],
        'edit' => ['label' => 'Editar tipos de documento', 'description' => 'Editar tipos de documento contable.'],
    ],

    'receivables' => [
        'view' => ['label' => 'Ver cuentas por cobrar', 'description' => 'Ver el listado de cuentas por cobrar.'],
        'create' => ['label' => 'Crear cuentas por cobrar', 'description' => 'Registrar nuevas cuentas por cobrar.'],
        'edit' => ['label' => 'Editar cuentas por cobrar', 'description' => 'Editar cuentas por cobrar existentes.'],
    ],

    'collections' => [
        'view' => ['label' => 'Ver cobros', 'description' => 'Ver el listado de cobros.'],
        'create' => ['label' => 'Crear cobros', 'description' => 'Registrar nuevos cobros.'],
        'cancel' => ['label' => 'Cancelar cobros', 'description' => 'Cancelar cobros ya registrados.'],
        'export' => ['label' => 'Exportar cobros', 'description' => 'Exportar cobros a Excel.'],
    ],

    'sales' => [
        'view' => ['label' => 'Ver ventas', 'description' => 'Ver el listado de ventas.'],
        'create' => ['label' => 'Crear ventas', 'description' => 'Registrar nuevas ventas.'],
        'edit' => ['label' => 'Editar ventas', 'description' => 'Editar ventas existentes.'],
        'cancel' => ['label' => 'Cancelar ventas', 'description' => 'Cancelar ventas ya registradas.'],
        'delete' => ['label' => 'Eliminar ventas', 'description' => 'Eliminar ventas existentes.'],
        'export' => ['label' => 'Exportar ventas', 'description' => 'Exportar ventas a Excel.'],
    ],

    'invoices' => [
        'view' => ['label' => 'Ver facturas', 'description' => 'Ver el listado de facturas.'],
        'export' => ['label' => 'Exportar facturas', 'description' => 'Exportar facturas a Excel.'],
    ],

    'ncf_sequences' => [
        'view' => ['label' => 'Ver secuencias de NCF', 'description' => 'Ver las secuencias de NCF configuradas.'],
        'manage' => ['label' => 'Gestionar secuencias de NCF', 'description' => 'Crear, editar y eliminar secuencias de NCF.'],
    ],

    'ncf' => [
        'reports' => ['label' => 'Ver reportes de NCF', 'description' => 'Ver reportes y bitácora de uso de NCF.'],
    ],

    'ncf_types' => [
        'manage' => ['label' => 'Gestionar tipos de NCF', 'description' => 'Crear, editar y eliminar tipos de NCF.'],
    ],

    'pos_terminals' => [
        'view' => ['label' => 'Ver terminales POS', 'description' => 'Ver el listado de terminales POS.'],
        'create' => ['label' => 'Crear terminales POS', 'description' => 'Registrar nuevas terminales POS.'],
        'edit' => ['label' => 'Editar terminales POS', 'description' => 'Editar terminales POS existentes.'],
        'delete' => ['label' => 'Eliminar terminales POS', 'description' => 'Eliminar terminales POS existentes.'],
    ],

    'pos_sessions' => [
        'manage' => ['label' => 'Gestionar turnos de caja POS', 'description' => 'Abrir, cerrar y administrar turnos de caja POS.'],
        'history' => ['label' => 'Ver historial de turnos POS', 'description' => 'Ver el historial de turnos POS.'],
    ],

    'pos_config' => [
        'view' => ['label' => 'Ver configuración del POS', 'description' => 'Ver la configuración del módulo POS.'],
        'update' => ['label' => 'Editar configuración del POS', 'description' => 'Editar la configuración del módulo POS.'],
    ],

    'quotes' => [
        'view' => ['label' => 'Ver cotizaciones', 'description' => 'Ver el listado de cotizaciones.'],
        'create' => ['label' => 'Crear cotizaciones', 'description' => 'Registrar nuevas cotizaciones.'],
        'edit' => ['label' => 'Editar cotizaciones', 'description' => 'Editar cotizaciones existentes.'],
        'convert' => ['label' => 'Convertir cotización en venta', 'description' => 'Convertir una cotización en venta.'],
        'cancel' => ['label' => 'Cancelar cotizaciones', 'description' => 'Cancelar cotizaciones existentes.'],
    ],

];
