<?php

// Registro estático de todo módulo servible (base/satélite). Ver docs/analisis/modulos-base-satelite.md §4
// y docs/features/v1.1.0.md Fase 3. Solo los módulos SATÉLITE viven aquí — los módulos base
// (CxC/CxP operativas, Cotizaciones, POS) nunca se registran ni se envuelven con middleware `module:`.
return [

    'sales.delivery_points' => [
        'label' => 'Puntos de Venta / Clientes de Ruta',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['admin/clients/pos', 'admin/clients/businessTypes'],
    ],

    'clients.field_assets' => [
        'label' => 'Equipos en Préstamo/Comodato',
        'category' => 'satellite',
        'depends_on' => ['clients.core'],
        'route_prefixes' => ['admin/clients/equipments', 'admin/clients/equipmentTypes'],
    ],

    'accounting.advanced' => [
        'label' => 'Contabilidad formal (partida doble)',
        'category' => 'satellite',
        'depends_on' => ['sales.core'],
        // OJO: no incluye receivables/payments — CxC y su abono operativo son base, se
        // resuelven aparte (REQ-02.8: PaymentService separa abono operativo de asiento
        // contable; solo el asiento formal, no la ruta, depende de este flag).
        'route_prefixes' => ['admin/accounting/journal_entries', 'admin/accounting/accounts', 'admin/accounting/dashboard'],
    ],

    'purchases.vendors' => [
        'label' => 'Compras / Proveedores formales',
        'category' => 'satellite',
        'depends_on' => ['inventory.core'],
        'route_prefixes' => ['admin/purchases'],
    ],

    'sales.ncf' => [
        'label' => 'Comprobantes Fiscales (NCF)',
        'category' => 'satellite',
        'depends_on' => ['sales.core'],
        'route_prefixes' => ['admin/sales/ncf'],
    ],

    'sales.credit_notes_b04' => [
        'label' => 'Nota de Crédito Fiscal (B04)',
        'category' => 'satellite',
        'depends_on' => ['sales.ncf'],
        'route_prefixes' => ['admin/sales/credit-notes'],
    ],

    // OJO: 'sales.quotes' NO va aquí — Cotizaciones es base, siempre activo, sin middleware `module:`.
];
