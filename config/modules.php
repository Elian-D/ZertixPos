<?php

// Registro estático de todo módulo servible. Ver docs/analisis/modulos-base-satelite.md §4
// y docs/features/v1.1.0.md Fase 3/10. Dos categorías conviven aquí desde la Fase 10
// (REQ-10.4): 'satellite' (apagado por defecto, gateado por Plan::assignTo()) y
// 'base_flexible' (encendido por defecto en TODO Plan, nunca tocado por Plan::assignTo() —
// solo lo cambia el dueño desde "Funcionalidades del Sistema", REQ-10.6/10.8). Los módulos
// base FIJOS (Auth, Config General, Productos, Clientes núcleo, Ventas/POS núcleo,
// Devoluciones) nunca se registran acá — no tienen flag, no son opt-out.
return [

    'sales.delivery_points' => [
        'label' => 'Clientes de Ruta',
        'description' => 'Gestiona rutas de entrega y clientes fijos con puntos de venta propios.',
        'icon' => 'heroicon-s-truck',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['app/clients/delivery-points', 'app/clients/businessTypes'],
    ],

    'clients.field_assets' => [
        'label' => 'Equipos en Préstamo',
        'description' => 'Rastrea equipos prestados o en comodato a tus clientes.',
        'icon' => 'heroicon-s-wrench-screwdriver',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['app/clients/equipments', 'app/clients/equipmentTypes'],
    ],

    // 'accounting.advanced' => [
    //     'label' => 'Contabilidad formal (partida doble)',
    //     'description' => 'Libro mayor y asientos de partida doble para contabilidad formal.',
    //     'icon' => 'heroicon-s-building-library',
    //     'category' => 'satellite',
    //     'depends_on' => ['sales.core'],
    //     // OJO: no incluye receivables/payments — CxC y su abono operativo son núcleo
    //     // flexible (REQ-02.8: CollectionService separa abono operativo de asiento
    //     // contable; solo el asiento formal, no la ruta, depende de este flag).
    //     'route_prefixes' => ['app/finance/journal_entries', 'app/finance/accounts', 'app/finance/dashboard'],
    // ],

    // 'purchases.vendors' => [
    //     'label' => 'Compras / Proveedores formales',
    //     'description' => 'Registra proveedores y órdenes de compra formales.',
    //     'icon' => 'heroicon-s-building-storefront',
    //     'category' => 'satellite',
    //     // Dependencia dura satélite→flexible: BLOQUEO explícito al guardar, nunca
    //     // cascada (ver SystemFeatures::HARD_DEPENDENCIES, REQ-10.7).
    //     'depends_on' => ['inventory.tracking'],
    //     'route_prefixes' => ['app/purchases'],
    // ],

    'sales.ncf' => [
        'label' => 'Comprobantes Fiscales (NCF)',
        'description' => 'Emite y controla comprobantes fiscales dominicanos (NCF) en tus facturas.',
        'icon' => 'heroicon-s-document-text',
        'category' => 'satellite',
        'depends_on' => ['sales.core'],
        'route_prefixes' => ['app/finance/ncf'],
    ],

    // 'sales.credit_notes_b04' NO es un módulo propio (revisión REQ-10.9) — el B04 es
    // el comprobante fiscal de UNA acción de Devoluciones (que todavía no existe), no
    // una funcionalidad independiente que alguien prenda/apague por separado. Cuando
    // se construya Devoluciones, esa acción concreta ("emitir con B04") simplemente
    // valida `module_enabled('sales.ncf')` en su propio código — no hace falta un
    // segundo flag ni una entrada acá, ni la cascada satélite→satélite que existía
    // en SystemFeatures::CASCADE_DEPENDENCIES (ya se quitó).

    // Núcleo flexible (REQ-10.4/§2.1.5 de modulos-base-satelite.md) — encendidos por
    // defecto en TODO Plan, jamás gateados por Plan::assignTo() (que solo filtra
    // category === 'satellite'). El dueño los apaga desde "Funcionalidades del
    // Sistema" si no le aplican a su operación; apagarlos congela la escritura de ese
    // dominio (REQ-10.5), nunca solo esconde la UI.
    'inventory.tracking' => [
        'label' => 'Control de Inventario',
        'description' => 'Descuenta y controla existencias por almacén en cada venta o movimiento.',
        'icon' => 'heroicon-s-archive-box',
        'category' => 'base_flexible',
        'route_prefixes' => ['app/inventory'],
    ],

    'sales.receivables' => [
        'label' => 'Cuentas por Cobrar',
        'description' => 'Vende a crédito y da seguimiento a los saldos pendientes de tus clientes.',
        'icon' => 'heroicon-s-banknotes',
        'category' => 'base_flexible',
        'route_prefixes' => ['app/finance/receivables'],
    ],

    // 'sales.payables' => [
    //     'label' => 'Cuentas por Pagar',
    //     'description' => 'Registra y da seguimiento a lo que tu negocio debe a proveedores.',
    //     'icon' => 'heroicon-s-credit-card',
    //     'category' => 'base_flexible',
    //     // Módulo pendiente de construir (docs/analisis/modulos-base-satelite.md §5,
    //     // ítem 3.5) — se registra ya con este flag para no repetir el error de
    //     // Contabilidad (construirlo fijo y desacoplarlo después). Sin ruta real
    //     // todavía: no hay middleware `module:` aplicado a nada por este flag.
    //     'route_prefixes' => ['app/finance/payables'],
    // ],

    'sales.quotes' => [
        'label' => 'Cotizaciones',
        'description' => 'Crea propuestas de venta antes de facturar y conviértelas en ventas reales.',
        'icon' => 'heroicon-s-document-duplicate',
        'category' => 'base_flexible',
        'route_prefixes' => ['app/sales/quotes'],
    ],

    // 'sales.propina_legal' => [
    //     'label' => 'Propina Legal',
    //     'description' => 'Cargo de servicio del 10% sobre la venta completa, para restaurantes y bares.',
    //     'icon' => 'heroicon-s-receipt-percent',
    //     // Ninguna de las dos categorías existentes calza del todo: no es 'satellite'
    //     // (no debería depender de a qué Plan/tier está suscrito el negocio — un
    //     // restaurante lo necesita sin importar el plan que pague) ni 'base_flexible'
    //     // tal cual está definida hoy (esos nacen ENCENDIDOS por defecto en todo Plan;
    //     // Propina Legal tiene que nacer APAGADA — la mayoría de instalaciones de
    //     // ZertixPOS hoy no son restaurantes). Falta una tercera categoría real
    //     // ('flexible_opt_in'?: apagado por defecto, togglable por el dueño, nunca
    //     // gateado por Plan) — no crearla solo para este módulo hasta que haga falta
    //     // un segundo caso igual; documentado acá para no reabrir esta discusión desde
    //     // cero. Mientras tanto, activar a mano vía InstallationModule::updateOrCreate()
    //     // (o el seeder que corresponda) es aceptable como solución temporal.
    //     'category' => 'base_flexible', // ver nota de arriba — placeholder, no literal
    //     'depends_on' => ['sales.core'],
    //     'route_prefixes' => [], // no tiene pantalla propia — ver lógica abajo
    // ],
    //
    // Diseño completo ya discutido (docs/features/v1.2.0.md Fase 5, REQ-5.7) — queda
    // documentado acá, no implementado, hasta que un cliente de restaurante/bar
    // realmente lo necesite (debería desarrollarse rápido con esto ya resuelto):
    //
    // 1. Deliberadamente FUERA del pivote product_taxes (config/impuestos.php) — no es
    //    un impuesto DGII (no se remite al fisco, es para los empleados) y se aplica a
    //    la venta completa, no producto por producto. Mezclarlo con ITBIS/ISC en el
    //    mismo pivote contaminaría los reportes fiscales 606/607.
    // 2. Con el módulo activo: el checkout POS ofrece un toggle "Aplicar Propina
    //    Legal" por venta (no es automático ni obligatorio en cada venta).
    // 3. La tasa (10%) vive en config('impuestos.propina_legal.rate') — el módulo
    //    solo decide SI el toggle existe en el checkout, no la tasa en sí. Evita
    //    tener la tasa flotando sin conectarse a ningún lado (gap detectado en la
    //    revisión de la Fase 5: el texto original de REQ-5.7 no dejaba explícito que
    //    el cálculo debe leer esa misma clave de config, no un 10% hardcodeado aparte).
    // 4. Si el toggle se marca: `sales.service_charge_amount` = net_amount * tasa,
    //    columna ya contemplada en la migración de REQ-5.2 (ver v1.2.0.md).
    // 5. Va a su propio pasivo contable (`service_charge_payable`, ver nota sobre
    //    roles contables en v1.2.0.md REQ-5.5) — no es ingreso del negocio.
    // 6. Con el módulo apagado (default): el toggle no aparece, `service_charge_amount`
    //    siempre es 0, sin excepción.
];
