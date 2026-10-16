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
        'view' => ['label' => 'Ver el inicio', 'description' => 'Ver la pantalla de inicio. Cada bloque aparece según los demás permisos (ventas, cobros, pagos, stock).'],
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
        'billing' => ['label' => 'Gestionar plan y suscripción', 'description' => 'Ver el plan actual, cambiarlo o renovar el pago con la pasarela.'],
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
        'dashboard' => ['label' => 'Ver reporte de inventario', 'description' => 'Ver el valor de la mercancía por almacén y categoría, lo que está bajo el mínimo y lo que no se mueve.'],
    ],

    'inventory_stocks' => [
        'view' => ['label' => 'Ver stock actual', 'description' => 'Ver el stock actual de productos.'],
        'update' => ['label' => 'Actualizar stock', 'description' => 'Ajustar manualmente el stock de productos.'],
        'export' => ['label' => 'Exportar stock', 'description' => 'Exportar el stock actual a Excel.'],
    ],

    'inventory_movements' => [
        'view' => ['label' => 'Ver movimientos de inventario', 'description' => 'Ver el historial de movimientos de inventario.'],
        'create_adjustment' => ['label' => 'Crear ajuste de inventario', 'description' => 'Agregar o sacar existencias a mano para corregir un error, con motivo y comentario.'],
    ],

    'inventory_counts' => [
        'view' => ['label' => 'Ver tomas físicas', 'description' => 'Ver las tomas físicas, su detalle y su PDF. En un conteo ciego sin aplicar no muestra las diferencias.'],
        'create' => ['label' => 'Crear tomas físicas', 'description' => 'Crear una toma física y cancelar un borrador.'],
        'count' => ['label' => 'Contar tomas físicas', 'description' => 'Entrar a la pantalla de conteo de una toma en borrador y guardar lo contado. Por sí solo no da acceso a la revisión ni a las diferencias.'],
        'apply' => ['label' => 'Revisar y aplicar tomas físicas', 'description' => 'Ver las diferencias de cualquier toma (también las ciegas) y aplicarla: ajusta las existencias del almacén.'],
    ],

    'inventory_wastes' => [
        'view' => ['label' => 'Ver mermas', 'description' => 'Ver las mermas, su detalle, su PDF y el valor perdido.'],
        'create' => ['label' => 'Registrar mermas', 'description' => 'Dar de baja mercancía dañada, vencida, robada o consumida. Se aplica al guardar.'],
        'void' => ['label' => 'Anular mermas', 'description' => 'Anular una merma: devuelve las existencias al almacén.'],
    ],

    'inventory_transfers' => [
        'view' => ['label' => 'Ver transferencias', 'description' => 'Ver las transferencias entre almacenes, su detalle y su PDF de despacho.'],
        'create' => ['label' => 'Crear transferencias', 'description' => 'Crear, editar y cancelar una transferencia en borrador.'],
        'send' => ['label' => 'Enviar transferencias', 'description' => 'Despachar una transferencia: la mercancía sale del almacén de origen y queda en tránsito.'],
        'receive' => ['label' => 'Recibir transferencias', 'description' => 'Confirmar lo que llegó al almacén de destino. Lo que falte queda como merma en tránsito.'],
    ],

    'accounting' => [
        'dashboard' => ['label' => 'Ver reportes financieros', 'description' => 'Ver el estado de resultados, el estado del negocio y la ganancia bruta del inicio.'],
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

    'vendors' => [
        'view' => ['label' => 'Ver proveedores', 'description' => 'Ver los proveedores, su detalle, lo que se les debe y sus gastos.'],
        'create' => ['label' => 'Crear proveedores', 'description' => 'Registrar proveedores nuevos.'],
        'edit' => ['label' => 'Editar proveedores', 'description' => 'Cambiar los datos, el contacto y los términos de pago de un proveedor.'],
        'delete' => ['label' => 'Eliminar proveedores', 'description' => 'Enviar un proveedor a la papelera o borrarlo definitivo si no tiene documentos.'],
        'restore' => ['label' => 'Restaurar proveedores', 'description' => 'Sacar un proveedor de la papelera.'],
    ],

    'purchase_orders' => [
        'view' => ['label' => 'Ver órdenes de compra', 'description' => 'Ver las órdenes de compra, sus recepciones y sus PDF.'],
        'create' => ['label' => 'Crear órdenes de compra', 'description' => 'Crear y editar una orden en borrador y marcarla como enviada al proveedor.'],
        'approve' => ['label' => 'Aprobar órdenes de compra', 'description' => 'Aprobar una orden en borrador: ya no se edita y se puede recibir.'],
        'cancel' => ['label' => 'Cancelar órdenes de compra', 'description' => 'Cancelar una orden que todavía no tiene recepciones.'],
    ],

    'purchase_receptions' => [
        'create' => ['label' => 'Recibir mercancía', 'description' => 'Registrar lo que llegó de una orden: entra el stock, se recalcula el costo y nace lo que se debe (o se paga de contado).'],
    ],

    'payables' => [
        'view' => ['label' => 'Ver cuentas por pagar', 'description' => 'Ver lo que se debe a proveedores, sus pagos y los recibos.'],
        'pay' => ['label' => 'Pagar cuentas por pagar', 'description' => 'Registrar pagos (totales o parciales) desde una cuenta de Cajas y Bancos.'],
        'void_payment' => ['label' => 'Anular pagos', 'description' => 'Anular un pago: el dinero vuelve a la cuenta y el monto a lo pendiente.'],
    ],

    'expenses' => [
        'view' => ['label' => 'Ver gastos', 'description' => 'Ver los gastos del negocio por categoría y fecha.'],
        'create' => ['label' => 'Registrar gastos', 'description' => 'Registrar gastos de contado (sale de una cuenta) o a crédito (nace una cuenta por pagar).'],
        'void' => ['label' => 'Anular gastos', 'description' => 'Anular un gasto: el dinero vuelve a la cuenta o se anula su cuenta por pagar con sus pagos.'],
    ],

    'financial_accounts' => [
        'view' => ['label' => 'Ver cajas y bancos', 'description' => 'Ver las cuentas de caja y banco, su saldo, su kardex y sus conciliaciones.'],
        'manage' => ['label' => 'Gestionar cajas y bancos', 'description' => 'Crear cuentas con su saldo inicial, editar sus datos y activarlas o desactivarlas.'],
        'move' => ['label' => 'Mover dinero', 'description' => 'Registrar depósitos, retiros y transferencias entre cuentas, y anularlos o corregir su monto.'],
        'reconcile' => ['label' => 'Conciliar cuentas', 'description' => 'Escribir el saldo real de una cuenta y ajustar la diferencia con su motivo.'],
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

    'returns' => [
        'create' => ['label' => 'Hacer devoluciones', 'description' => 'Registrar devoluciones y cambios de productos de una venta.'],
        'void' => ['label' => 'Anular devoluciones', 'description' => 'Anular una devolución ya registrada.'],
    ],

];
